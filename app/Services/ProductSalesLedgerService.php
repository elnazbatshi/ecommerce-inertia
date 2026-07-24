<?php

namespace App\Services;

use App\Http\Resources\ProductSalesLedgerItemResource;
use App\Http\Resources\ProductSalesSummaryResource;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class ProductSalesLedgerService
{
    private const TRANSACTION_SORT_MAP = [
        'paid_at' => 'orders.paid_at',
        'order_number' => 'orders.order_number',
        'product_name' => 'order_items.product_name',
        'product_sku' => 'order_items.sku',
        'quantity' => 'order_items.quantity',
        'unit_price' => 'order_items.unit_price',
        'discount' => 'order_items.discount_price',
        'total_price' => 'order_items.total_price',
        'customer_name' => 'customers.name',
        'transaction_id' => 'latest_paid_payments.transaction_id',
    ];

    private const PRODUCT_SORT_MAP = [
        'product_name' => 'product_name',
        'product_sku' => 'product_sku',
        'quantity_sold' => 'quantity_sold',
        'orders_count' => 'orders_count',
        'gross_sales' => 'gross_sales',
        'discount_total' => 'discount_total',
        'net_sales' => 'net_sales',
        'average_unit_price' => 'average_unit_price',
        'last_sold_at' => 'last_sold_at',
        'sales_share' => 'sales_share',
    ];

    public function transactions(array $filters): LengthAwarePaginator
    {
        $query = $this->baseSalesQuery();
        $this->applyFilters($query, $filters);
        $this->applyTransactionSort($query, $filters);

        return $query
            ->paginate(
                perPage: $filters['per_page'],
                columns: ['*'],
                pageName: 'page',
                page: $filters['page'],
            )
            ->withQueryString()
            ->through(fn (OrderItem $item) => ProductSalesLedgerItemResource::make($item)->resolve());
    }

    public function statistics(array $filters): array
    {
        $query = $this->baseSalesQuery(withRelations: false);
        $this->applyFilters($query, $filters);

        $row = $query
            ->select([])
            ->selectRaw('COALESCE(SUM(order_items.total_price), 0) as total_sales')
            ->selectRaw('COUNT(DISTINCT order_items.order_id) as orders_count')
            ->selectRaw('COALESCE(SUM(order_items.quantity), 0) as items_sold')
            ->selectRaw('COALESCE(SUM(GREATEST((order_items.unit_price - COALESCE(order_items.discount_price, order_items.unit_price)) * order_items.quantity, 0)), 0) as discount_total')
            ->selectRaw('COUNT(DISTINCT order_items.product_id) as products_count')
            ->toBase()
            ->first();

        $totalSales = (float) ($row->total_sales ?? 0);
        $ordersCount = (int) ($row->orders_count ?? 0);

        return [
            'total_sales' => $totalSales,
            'orders_count' => $ordersCount,
            'items_sold' => (int) ($row->items_sold ?? 0),
            'discount_total' => (float) ($row->discount_total ?? 0),
            'average_order_value' => $ordersCount > 0 ? round($totalSales / $ordersCount, 2) : 0,
            'products_count' => (int) ($row->products_count ?? 0),
        ];
    }

    public function productSummaries(array $filters): LengthAwarePaginator
    {
        $totalSales = $this->statistics($filters)['total_sales'];
        $query = $this->productSummaryBaseQuery($filters, $totalSales);
        $this->applyProductSort($query, $filters);

        return $query
            ->paginate(
                perPage: $filters['per_page'],
                columns: ['*'],
                pageName: 'page',
                page: $filters['page'],
            )
            ->withQueryString()
            ->through(fn ($row) => ProductSalesSummaryResource::make($row)->resolve());
    }

    public function chartData(array $filters): array
    {
        return [
            'timeline' => $this->timelineChart($filters),
            'top_products' => $this->topProductsChart($filters),
            'payment_methods' => $this->paymentMethodChart($filters),
        ];
    }

    public function productOptions(?string $search = null): array
    {
        return Product::query()
            ->select(['id', 'name', 'sku'])
            ->when($search, function (Builder $query, string $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->limit(30)
            ->get()
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'label' => trim($product->name . ($product->sku ? " ({$product->sku})" : '')),
            ])
            ->all();
    }

    public function baseSalesQuery(bool $withRelations = true): Builder
    {
        $query = OrderItem::query()
            ->select([
                'order_items.*',
                'latest_paid_payments.method as latest_payment_method',
                'latest_paid_payments.transaction_id as latest_payment_transaction_id',
                'latest_paid_payments.reference_id as latest_payment_reference_id',
                'latest_paid_payments.paid_at as latest_payment_paid_at',
            ])
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('customers', 'customers.id', '=', 'orders.customer_id')
            ->leftJoinSub($this->latestPaidPaymentsSubquery(), 'latest_paid_payments', function ($join) {
                $join->on('latest_paid_payments.order_id', '=', 'orders.id');
            })
            ->where('orders.payment_status', 'paid')
            ->whereNotNull('orders.paid_at')
            ->whereNotIn('orders.status', ['cancelled', 'returned'])
            ->whereNull('orders.deleted_at');

        if ($withRelations) {
            $query->with([
                'product' => fn ($query) => $query->withTrashed()->select(['id', 'slug', 'main_image', 'deleted_at']),
                'order:id,order_number,customer_id,payment_method_name,status,payment_status,paid_at',
                'order.customer:id,name,phone',
            ]);
        }

        return $query;
    }

    private function productSummaryBaseQuery(array $filters, float $totalSales): Builder
    {
        $query = $this->baseSalesQuery(withRelations: false);
        $this->applyFilters($query, $filters);

        return $query
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->select([])
            ->selectRaw('MIN(order_items.id) as id')
            ->selectRaw('order_items.product_id as product_id')
            ->selectRaw('MAX(products.slug) as product_slug')
            ->selectRaw('MAX(products.main_image) as product_image')
            ->selectRaw('MAX(products.deleted_at) as product_deleted_at')
            ->selectRaw('COALESCE(order_items.product_name, CONCAT("product-", order_items.product_id)) as product_name')
            ->selectRaw('COALESCE(order_items.sku, "") as product_sku')
            ->selectRaw('SUM(order_items.quantity) as quantity_sold')
            ->selectRaw('COUNT(DISTINCT order_items.order_id) as orders_count')
            ->selectRaw('SUM(order_items.unit_price * order_items.quantity) as gross_sales')
            ->selectRaw('SUM(GREATEST((order_items.unit_price - COALESCE(order_items.discount_price, order_items.unit_price)) * order_items.quantity, 0)) as discount_total')
            ->selectRaw('SUM(order_items.total_price) as net_sales')
            ->selectRaw('CASE WHEN SUM(order_items.quantity) > 0 THEN SUM(order_items.total_price) / SUM(order_items.quantity) ELSE 0 END as average_unit_price')
            ->selectRaw('MAX(orders.paid_at) as last_sold_at')
            ->selectRaw($totalSales > 0 ? '(SUM(order_items.total_price) / ? * 100) as sales_share' : '0 as sales_share', $totalSales > 0 ? [$totalSales] : [])
            ->groupBy('order_items.product_id', 'order_items.product_name', 'order_items.sku');
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        $query
            ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->where('orders.order_number', 'like', "%{$search}%")
                        ->orWhere('order_items.product_name', 'like', "%{$search}%")
                        ->orWhere('order_items.sku', 'like', "%{$search}%")
                        ->orWhere('customers.name', 'like', "%{$search}%")
                        ->orWhere('customers.phone', 'like', "%{$search}%")
                        ->orWhere('latest_paid_payments.transaction_id', 'like', "%{$search}%")
                        ->orWhere('latest_paid_payments.reference_id', 'like', "%{$search}%");
                });
            })
            ->when($filters['order_number'] ?? null, fn (Builder $query, string $value) => $query->where('orders.order_number', 'like', "%{$value}%"))
            ->when($filters['transaction_id'] ?? null, function (Builder $query, string $value) {
                $query->where(function (Builder $query) use ($value) {
                    $query->where('latest_paid_payments.transaction_id', 'like', "%{$value}%")
                        ->orWhere('latest_paid_payments.reference_id', 'like', "%{$value}%");
                });
            })
            ->when($filters['product_query'] ?? null, function (Builder $query, string $value) {
                $query->where(function (Builder $query) use ($value) {
                    $query->where('order_items.product_name', 'like', "%{$value}%")
                        ->orWhere('order_items.sku', 'like', "%{$value}%");
                });
            })
            ->when($filters['product_id'] ?? null, fn (Builder $query, int $id) => $query->where('order_items.product_id', $id))
            ->when($filters['customer_query'] ?? null, function (Builder $query, string $value) {
                $query->where(function (Builder $query) use ($value) {
                    $query->where('customers.name', 'like', "%{$value}%")
                        ->orWhere('customers.phone', 'like', "%{$value}%");
                });
            })
            ->when($filters['payment_method'] ?? null, fn (Builder $query, string $value) => $query->where('latest_paid_payments.method', $value))
            ->when($filters['order_status'] ?? null, fn (Builder $query, string $value) => $query->where('orders.status', $value))
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('orders.paid_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('orders.paid_at', '<=', $date));
    }

    private function applyTransactionSort(Builder $query, array $filters): void
    {
        $field = $filters['sort_field'] ?? null;
        $direction = ($filters['sort_order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        if ($field && isset(self::TRANSACTION_SORT_MAP[$field])) {
            $query->orderBy(self::TRANSACTION_SORT_MAP[$field], $direction);
        } else {
            $query->orderByDesc('orders.paid_at');
        }

        $query->orderByDesc('order_items.id');
    }

    private function applyProductSort(Builder $query, array $filters): void
    {
        $field = $filters['product_sort_field'] ?? 'net_sales';
        $direction = ($filters['sort_order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $query->orderBy(self::PRODUCT_SORT_MAP[$field] ?? 'net_sales', $direction)
            ->orderByDesc('last_sold_at');
    }

    private function timelineChart(array $filters): array
    {
        $query = $this->baseSalesQuery(withRelations: false);
        $this->applyFilters($query, $filters);

        $select = match ($filters['chart_grouping']) {
            'week' => 'YEARWEEK(orders.paid_at, 3)',
            'month' => 'DATE_FORMAT(orders.paid_at, "%Y-%m")',
            default => 'DATE(orders.paid_at)',
        };

        return $query
            ->select([])
            ->selectRaw("{$select} as label")
            ->selectRaw('SUM(order_items.total_price) as total_sales')
            ->selectRaw('SUM(order_items.quantity) as quantity_sold')
            ->toBase()
            ->groupBy('label')
            ->orderBy('label')
            ->get()
            ->map(fn ($row) => [
                'label' => (string) $row->label,
                'total_sales' => (float) $row->total_sales,
                'quantity_sold' => (int) $row->quantity_sold,
            ])
            ->all();
    }

    private function topProductsChart(array $filters): array
    {
        $query = $this->productSummaryBaseQuery($filters, max($this->statistics($filters)['total_sales'], 1));

        return $query
            ->orderByDesc('net_sales')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'label' => $row->product_name,
                'net_sales' => (float) $row->net_sales,
                'quantity_sold' => (int) $row->quantity_sold,
            ])
            ->all();
    }

    private function paymentMethodChart(array $filters): array
    {
        $query = $this->baseSalesQuery(withRelations: false);
        $this->applyFilters($query, $filters);

        return $query
            ->select([])
            ->selectRaw('COALESCE(latest_paid_payments.method, "unknown") as method')
            ->selectRaw('SUM(order_items.total_price) as total_sales')
            ->selectRaw('COUNT(DISTINCT order_items.order_id) as orders_count')
            ->toBase()
            ->groupBy('method')
            ->orderByDesc('total_sales')
            ->get()
            ->map(fn ($row) => [
                'method' => (string) $row->method,
                'total_sales' => (float) $row->total_sales,
                'orders_count' => (int) $row->orders_count,
            ])
            ->all();
    }

    private function latestPaidPaymentsSubquery(): Builder
    {
        return Payment::query()
            ->select('payments.*')
            ->where('payments.status', 'paid')
            ->whereNotNull('payments.paid_at')
            ->whereRaw('payments.id = (
                select p2.id
                from payments as p2
                where p2.order_id = payments.order_id
                    and p2.status = ?
                    and p2.paid_at is not null
                    and p2.deleted_at is null
                order by p2.paid_at desc, p2.id desc
                limit 1
            )', ['paid']);
    }
}
