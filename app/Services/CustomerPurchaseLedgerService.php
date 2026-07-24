<?php

namespace App\Services;

use App\Http\Resources\CustomerPurchaseLedgerItemResource;
use App\Http\Resources\CustomerPurchasePaymentResource;
use App\Http\Resources\CustomerPurchasedProductResource;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class CustomerPurchaseLedgerService
{
    private const VALUABLE_CUSTOMER_THRESHOLD = 50000000;
    private const NEW_CUSTOMER_DAYS = 30;
    private const LOYAL_ORDER_COUNT = 4;
    private const RECENT_PURCHASE_DAYS = 90;
    private const INACTIVE_DAYS = 180;
    private const AT_RISK_INTERVAL_MULTIPLIER = 1.5;

    private const ORDER_SORT_MAP = [
        'order_number' => 'orders.order_number',
        'created_at' => 'orders.created_at',
        'paid_at' => 'orders.paid_at',
        'items_count' => 'items_count',
        'subtotal' => 'orders.subtotal',
        'discount' => 'orders.discount_total',
        'shipping_amount' => 'orders.shipping_cost',
        'total_amount' => 'orders.total',
        'status' => 'orders.status',
    ];

    private const PRODUCT_SORT_MAP = [
        'product_name' => 'product_name',
        'product_sku' => 'product_sku',
        'quantity_purchased' => 'quantity_purchased',
        'orders_count' => 'orders_count',
        'gross_amount' => 'gross_amount',
        'discount_amount' => 'discount_amount',
        'net_amount' => 'net_amount',
        'average_unit_price' => 'average_unit_price',
        'first_purchased_at' => 'first_purchased_at',
        'last_purchased_at' => 'last_purchased_at',
    ];

    private const PAYMENT_SORT_MAP = [
        'order_number' => 'orders.order_number',
        'method' => 'payments.method',
        'amount' => 'payments.amount',
        'transaction_id' => 'payments.transaction_id',
        'reference_id' => 'payments.reference_id',
        'paid_at' => 'payments.paid_at',
        'status' => 'payments.status',
    ];

    public function selectedCustomer(?int $customerId): ?array
    {
        if (! $customerId) {
            return null;
        }

        $customer = Customer::query()
            ->withCount('addresses')
            ->find($customerId);

        if (! $customer) {
            return null;
        }

        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'phone' => $customer->phone,
            'email' => $customer->email,
            'status' => $customer->status,
            'last_login_at' => $customer->last_login_at?->toDateTimeString(),
            'created_at' => $customer->created_at?->toDateTimeString(),
            'addresses_count' => (int) $customer->addresses_count,
            'admin_url' => route('admin.customers.edit', $customer, false),
        ];
    }

    public function searchCustomers(?string $search, ?int $selectedId = null): array
    {
        if (! $search && ! $selectedId) {
            return [];
        }

        if ($search && mb_strlen($search) < 2 && ! $selectedId) {
            return [];
        }

        return Customer::query()
            ->select(['id', 'name', 'phone', 'email'])
            ->when($selectedId, fn (Builder $query) => $query->where('id', $selectedId))
            ->when($search && mb_strlen($search) >= 2, function (Builder $query) use ($search) {
                $query->orWhere(function (Builder $query) use ($search) {
                    $query->where('id', $search)
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(fn (Customer $customer) => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'label' => trim(($customer->name ?: 'مشتری بدون نام').' - '.$customer->phone.' #'.$customer->id),
            ])
            ->values()
            ->all();
    }

    public function statistics(array $filters): array
    {
        if (! $filters['customer_id']) {
            return $this->emptyStatistics();
        }

        $orderStats = (clone $this->baseFinalOrdersQuery($filters))
            ->selectRaw('COALESCE(SUM(orders.total), 0) as total_spent')
            ->selectRaw('COUNT(DISTINCT orders.id) as orders_count')
            ->selectRaw('COALESCE(SUM(orders.discount_total), 0) as discount_total')
            ->selectRaw('COALESCE(SUM(orders.shipping_cost), 0) as shipping_total')
            ->selectRaw('MAX(orders.paid_at) as last_purchase_at')
            ->selectRaw('MIN(orders.paid_at) as first_purchase_at')
            ->toBase()
            ->first();

        $itemStats = $this->baseFinalOrderItemsQuery($filters)
            ->tap(fn (Builder $query) => $this->applyItemFilters($query, $filters))
            ->selectRaw('COALESCE(SUM(order_items.quantity), 0) as items_count')
            ->selectRaw('COUNT(DISTINCT order_items.product_id) as products_count')
            ->toBase()
            ->first();

        $totalSpent = (float) ($orderStats->total_spent ?? 0);
        $ordersCount = (int) ($orderStats->orders_count ?? 0);

        return [
            'total_spent' => $totalSpent,
            'orders_count' => $ordersCount,
            'items_count' => (int) ($itemStats->items_count ?? 0),
            'products_count' => (int) ($itemStats->products_count ?? 0),
            'discount_total' => (float) ($orderStats->discount_total ?? 0),
            'shipping_total' => (float) ($orderStats->shipping_total ?? 0),
            'average_order_value' => $ordersCount > 0 ? round($totalSpent / $ordersCount, 2) : 0,
            'last_purchase_at' => $orderStats->last_purchase_at ?? null,
            'first_purchase_at' => $orderStats->first_purchase_at ?? null,
        ];
    }

    public function orders(array $filters): LengthAwarePaginator
    {
        if (! $filters['customer_id']) {
            return $this->emptyPaginator($filters);
        }

        $query = $this->baseFinalOrdersQuery($filters)
            ->select([
                'orders.*',
                'latest_paid_payments.method as latest_payment_method',
                'latest_paid_payments.transaction_id as latest_payment_transaction_id',
                'latest_paid_payments.reference_id as latest_payment_reference_id',
            ])
            ->withCount('items')
            ->with(['items.product' => fn ($query) => $query->withTrashed()->select(['id', 'slug', 'main_image', 'deleted_at'])]);

        $this->applyOrderSort($query, $filters);

        return $query
            ->paginate($filters['per_page'], ['*'], 'page', $filters['page'])
            ->withQueryString()
            ->through(fn (Order $order) => CustomerPurchaseLedgerItemResource::make($order)->resolve());
    }

    public function purchasedProducts(array $filters): LengthAwarePaginator
    {
        if (! $filters['customer_id']) {
            return $this->emptyPaginator($filters);
        }

        $query = $this->baseFinalOrderItemsQuery($filters)
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->tap(fn (Builder $query) => $this->applyItemFilters($query, $filters))
            ->selectRaw('MIN(order_items.id) as id')
            ->selectRaw('COALESCE(CONCAT("product:", order_items.product_id), CONCAT("snapshot:", COALESCE(order_items.sku, ""), ":", order_items.product_name)) as group_key')
            ->selectRaw('order_items.product_id as product_id')
            ->selectRaw('MAX(products.slug) as product_slug')
            ->selectRaw('MAX(products.main_image) as product_image')
            ->selectRaw('MAX(products.deleted_at) as product_deleted_at')
            ->selectRaw('COALESCE(order_items.product_name, CONCAT("product-", order_items.product_id)) as product_name')
            ->selectRaw('COALESCE(order_items.sku, "") as product_sku')
            ->selectRaw('SUM(order_items.quantity) as quantity_purchased')
            ->selectRaw('COUNT(DISTINCT order_items.order_id) as orders_count')
            ->selectRaw('SUM(order_items.unit_price * order_items.quantity) as gross_amount')
            ->selectRaw('SUM(GREATEST((order_items.unit_price - COALESCE(order_items.discount_price, order_items.unit_price)) * order_items.quantity, 0)) as discount_amount')
            ->selectRaw('SUM(order_items.total_price) as net_amount')
            ->selectRaw('CASE WHEN SUM(order_items.quantity) > 0 THEN SUM(order_items.total_price) / SUM(order_items.quantity) ELSE 0 END as average_unit_price')
            ->selectRaw('MIN(orders.paid_at) as first_purchased_at')
            ->selectRaw('MAX(orders.paid_at) as last_purchased_at')
            ->groupBy('order_items.product_id', 'order_items.product_name', 'order_items.sku');

        $this->applyProductSort($query, $filters);

        return $query
            ->paginate($filters['per_page'], ['*'], 'page', $filters['page'])
            ->withQueryString()
            ->through(fn ($row) => CustomerPurchasedProductResource::make($row)->resolve());
    }

    public function payments(array $filters): LengthAwarePaginator
    {
        if (! $filters['customer_id']) {
            return $this->emptyPaginator($filters);
        }

        $query = Payment::query()
            ->select([
                'payments.*',
                'orders.order_number',
            ])
            ->join('orders', 'orders.id', '=', 'payments.order_id')
            ->leftJoinSub($this->latestPaidPaymentsSubquery(), 'latest_paid_payments', function ($join) {
                $join->on('latest_paid_payments.order_id', '=', 'orders.id');
            })
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

        $this->applyFinalOrderFilters($query, $filters);
        $this->applyPaymentSort($query, $filters);

        return $query
            ->paginate($filters['per_page'], ['*'], 'page', $filters['page'])
            ->withQueryString()
            ->through(fn (Payment $payment) => CustomerPurchasePaymentResource::make($payment)->resolve());
    }

    public function analytics(array $filters): array
    {
        if (! $filters['customer_id']) {
            return [];
        }

        return [
            'purchase_amount_trend' => $this->orderTimeline($filters),
            'order_count_trend' => $this->orderTimeline($filters),
            'items_quantity_trend' => $this->itemTimeline($filters),
            'favorite_products' => $this->topProducts($filters, 'quantity_purchased'),
            'top_spent_products' => $this->topProducts($filters, 'net_amount'),
            'payment_methods' => $this->paymentMethodShares($filters),
            'average_days_between_orders' => $this->averageDaysBetweenOrders($filters),
        ];
    }

    public function lifetimeValue(int $customerId): float
    {
        return (float) $this->baseAllFinalOrdersQuery($customerId)
            ->sum('orders.total');
    }

    public function lastPurchase(int $customerId): ?array
    {
        $order = $this->baseAllFinalOrdersQuery($customerId)
            ->select(['orders.*'])
            ->withCount('items')
            ->with(['items.product' => fn ($query) => $query->withTrashed()->select(['id', 'slug', 'main_image', 'deleted_at'])])
            ->orderByDesc('orders.paid_at')
            ->orderByDesc('orders.id')
            ->first();

        if (! $order) {
            return null;
        }

        $firstItem = $order->items->first();

        return [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'paid_at' => $order->paid_at?->toDateTimeString(),
            'days_since' => $order->paid_at ? (int) $order->paid_at->diffInDays(now()) : null,
            'total_amount' => (float) $order->total,
            'items_count' => (int) $order->items_count,
            'first_product_name' => $firstItem?->product_name,
            'first_product_image' => $firstItem?->product?->main_image,
            'other_items_count' => max((int) $order->items_count - 1, 0),
            'order_url' => route('admin.orders.show', $order, false),
        ];
    }

    public function purchaseFrequency(int $customerId): array
    {
        $dates = $this->finalPurchaseDates($customerId);
        $count = $dates->count();

        if ($count === 0) {
            return $this->emptyPurchaseFrequency();
        }

        $daysSinceLast = (int) \Illuminate\Support\Carbon::parse($dates->last())->diffInDays(now());

        if ($count < 2) {
            return [
                'average_interval_days' => null,
                'minimum_interval_days' => null,
                'maximum_interval_days' => null,
                'days_since_last_purchase' => $daysSinceLast,
                'repeat_purchase_count' => 0,
                'total_purchase_count' => $count,
            ];
        }

        $intervals = [];
        for ($i = 1; $i < $count; $i++) {
            $intervals[] = \Illuminate\Support\Carbon::parse($dates[$i - 1])->diffInDays(\Illuminate\Support\Carbon::parse($dates[$i]));
        }

        return [
            'average_interval_days' => round(array_sum($intervals) / count($intervals), 1),
            'minimum_interval_days' => min($intervals),
            'maximum_interval_days' => max($intervals),
            'days_since_last_purchase' => $daysSinceLast,
            'repeat_purchase_count' => max($count - 1, 0),
            'total_purchase_count' => $count,
        ];
    }

    public function emptyPurchaseFrequency(): array
    {
        return [
            'average_interval_days' => null,
            'minimum_interval_days' => null,
            'maximum_interval_days' => null,
            'days_since_last_purchase' => null,
            'repeat_purchase_count' => 0,
            'total_purchase_count' => 0,
        ];
    }

    public function customerSegment(int $customerId): array
    {
        $frequency = $this->purchaseFrequency($customerId);
        $lifetimeValue = $this->lifetimeValue($customerId);
        $ordersCount = $frequency['total_purchase_count'];
        $daysSinceLast = $frequency['days_since_last_purchase'];
        $averageInterval = $frequency['average_interval_days'];
        $firstPurchase = $this->finalPurchaseDates($customerId)->first();
        $daysSinceFirst = $firstPurchase ? \Illuminate\Support\Carbon::parse($firstPurchase)->diffInDays(now()) : null;

        if ($ordersCount === 0) {
            return ['key' => 'new', 'label' => 'بدون خرید قطعی', 'severity' => 'secondary', 'description' => 'هنوز خرید قطعی برای این مشتری ثبت نشده است.'];
        }

        if ($daysSinceLast !== null && $daysSinceLast > self::INACTIVE_DAYS) {
            return ['key' => 'inactive', 'label' => 'مشتری غیرفعال', 'severity' => 'secondary', 'description' => 'آخرین خرید مشتری بیش از ۱۸۰ روز قبل بوده است.'];
        }

        if ($ordersCount >= 2 && $averageInterval && $daysSinceLast > ($averageInterval * self::AT_RISK_INTERVAL_MULTIPLIER)) {
            return ['key' => 'at_risk', 'label' => 'در معرض ریزش', 'severity' => 'warn', 'description' => 'از الگوی معمول فاصله خرید مشتری بیشتر گذشته است.'];
        }

        if ($lifetimeValue >= self::VALUABLE_CUSTOMER_THRESHOLD) {
            return ['key' => 'valuable', 'label' => 'مشتری ارزشمند', 'severity' => 'success', 'description' => 'ارزش طول عمر خرید مشتری از آستانه ارزشمند عبور کرده است.'];
        }

        if ($ordersCount >= self::LOYAL_ORDER_COUNT && $daysSinceLast <= self::RECENT_PURCHASE_DAYS) {
            return ['key' => 'loyal', 'label' => 'مشتری وفادار', 'severity' => 'success', 'description' => 'تعداد خرید بالا و خرید نسبتاً اخیر دارد.'];
        }

        if ($ordersCount === 1 && $daysSinceFirst !== null && $daysSinceFirst <= self::NEW_CUSTOMER_DAYS) {
            return ['key' => 'new', 'label' => 'مشتری جدید', 'severity' => 'info', 'description' => 'اولین خرید قطعی مشتری در ۳۰ روز اخیر ثبت شده است.'];
        }

        return ['key' => 'regular', 'label' => 'مشتری معمولی', 'severity' => 'info', 'description' => 'الگوی خرید مشتری در محدوده معمول قرار دارد.'];
    }

    public function frequentlyPurchasedProducts(array $filters, int $limit = 10): array
    {
        if (! $filters['customer_id']) {
            return [];
        }

        return $this->baseFinalOrderItemsQuery($filters)
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->selectRaw('MIN(order_items.id) as id')
            ->selectRaw('order_items.product_id')
            ->selectRaw('order_items.product_variant_id')
            ->selectRaw('MAX(products.slug) as product_slug')
            ->selectRaw('MAX(products.main_image) as image_url')
            ->selectRaw('MAX(products.deleted_at) as product_deleted_at')
            ->selectRaw('COALESCE(order_items.product_name, CONCAT("product-", order_items.product_id)) as product_name')
            ->selectRaw('COALESCE(order_items.sku, "") as product_sku')
            ->selectRaw('SUM(order_items.quantity) as quantity_purchased')
            ->selectRaw('COUNT(DISTINCT order_items.order_id) as orders_count')
            ->selectRaw('SUM(order_items.total_price) as net_amount')
            ->selectRaw('MAX(orders.paid_at) as last_purchased_at')
            ->groupBy('order_items.product_id', 'order_items.product_variant_id', 'order_items.product_name', 'order_items.sku')
            ->orderByDesc('quantity_purchased')
            ->orderByDesc('orders_count')
            ->orderByDesc('net_amount')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'product_id' => $row->product_id,
                'product_variant_id' => $row->product_variant_id,
                'product_name' => $row->product_name,
                'product_sku' => $row->product_sku ?: null,
                'image_url' => $row->image_url,
                'quantity_purchased' => (int) $row->quantity_purchased,
                'orders_count' => (int) $row->orders_count,
                'net_amount' => (float) $row->net_amount,
                'last_purchased_at' => $row->last_purchased_at,
                'days_since_last_purchase' => $row->last_purchased_at ? (int) \Illuminate\Support\Carbon::parse($row->last_purchased_at)->diffInDays(now()) : null,
                'is_deleted' => $row->product_id === null || $row->product_deleted_at !== null,
                'product_url' => $row->product_id && ! $row->product_deleted_at && $row->product_slug ? route('admin.products.show', $row->product_slug, false) : null,
            ])
            ->all();
    }

    public function repurchaseCandidates(array $filters, int $limit = 10): array
    {
        return collect($this->productPurchasePatterns((int) $filters['customer_id']))
            ->filter(fn ($item) => $item['purchase_dates_count'] >= 2 && $item['average_interval_days'] > 0)
            ->map(fn ($item) => $this->withOverdueStatus($item))
            ->sortByDesc('overdue_ratio')
            ->take($limit)
            ->values()
            ->all();
    }

    public function nextPurchaseSuggestions(array $filters, int $limit = 5): array
    {
        return collect($this->repurchaseCandidates($filters, 30))
            ->filter(fn ($item) => ! $item['is_deleted'] && $item['is_active_product'] && $item['overdue_ratio'] >= 0.8)
            ->map(function ($item) {
                $priority = $item['overdue_ratio'] >= 1.8 ? 'high' : ($item['overdue_ratio'] >= 1.2 ? 'medium' : 'low');
                $nextPurchaseInDays = max((int) round($item['average_interval_days'] - $item['days_since_last_purchase']), 0);

                return [
                    ...$item,
                    'priority' => $priority,
                    'next_purchase_in_days' => $nextPurchaseInDays,
                    'reason' => "این مشتری این کالا را به‌طور میانگین هر {$item['average_interval_days']} روز خریداری کرده و {$item['days_since_last_purchase']} روز از آخرین خرید گذشته است.",
                ];
            })
            ->take($limit)
            ->values()
            ->all();
    }

    public function additionalInsights(array $filters): array
    {
        if (! $filters['customer_id']) {
            return [];
        }

        $orderStats = (clone $this->baseFinalOrdersQuery($filters))
            ->selectRaw('MAX(orders.total) as max_order_total')
            ->selectRaw('MIN(orders.total) as min_order_total')
            ->selectRaw('COUNT(DISTINCT DATE_FORMAT(orders.paid_at, "%Y-%m")) as active_months')
            ->toBase()
            ->first();

        $frequency = $this->purchaseFrequency((int) $filters['customer_id']);
        $popularByQuantity = collect($this->frequentlyPurchasedProducts($filters, 1))->first();
        $popularByAmount = collect($this->frequentlyPurchasedProducts($filters, 30))->sortByDesc('net_amount')->first();
        $topPayment = collect($this->paymentMethodShares($filters))->first();

        return [
            'repeat_order_ratio' => $frequency['total_purchase_count'] > 1 ? round(($frequency['total_purchase_count'] - 1) / $frequency['total_purchase_count'], 2) : 0,
            'active_months' => (int) ($orderStats->active_months ?? 0),
            'max_order_total' => (float) ($orderStats->max_order_total ?? 0),
            'min_order_total' => (float) ($orderStats->min_order_total ?? 0),
            'favorite_product_by_quantity' => $popularByQuantity['product_name'] ?? null,
            'favorite_product_by_amount' => $popularByAmount['product_name'] ?? null,
            'favorite_payment_method' => $topPayment['method'] ?? null,
        ];
    }

    public function timeline(array $filters): LengthAwarePaginator
    {
        if (! $filters['customer_id']) {
            return new LengthAwarePaginator([], 0, $filters['timeline_per_page'], $filters['timeline_page']);
        }

        return $this->baseFinalOrdersQuery($filters)
            ->select([
                'orders.*',
                'latest_paid_payments.method as latest_payment_method',
            ])
            ->withCount('items')
            ->with(['items' => fn ($query) => $query->select(['id', 'order_id', 'product_name', 'variant_name', 'sku', 'quantity', 'total_price'])->orderBy('id')])
            ->orderByDesc('orders.paid_at')
            ->orderByDesc('orders.id')
            ->paginate($filters['timeline_per_page'], ['*'], 'timeline_page', $filters['timeline_page'])
            ->withQueryString()
            ->through(fn (Order $order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'paid_at' => $order->paid_at?->toDateTimeString(),
                'total_amount' => (float) $order->total,
                'items_count' => (int) $order->items_count,
                'products' => $order->items->take(3)->map(fn ($item) => $item->product_name)->values(),
                'payment_method' => $order->latest_payment_method ?: $order->payment_method_name,
                'status' => $order->status,
                'order_url' => route('admin.orders.show', $order, false),
            ]);
    }

    public function paymentMethodOptions(): array
    {
        return collect(Payment::METHODS)
            ->map(fn (string $method) => ['label' => $method, 'value' => $method])
            ->values()
            ->all();
    }

    public function orderStatusOptions(): array
    {
        return collect(Order::STATUSES)
            ->reject(fn (string $status) => in_array($status, ['cancelled', 'returned'], true))
            ->map(fn (string $status) => ['label' => $status, 'value' => $status])
            ->values()
            ->all();
    }

    private function baseFinalOrdersQuery(array $filters): Builder
    {
        $query = Order::query()
            ->leftJoinSub($this->latestPaidPaymentsSubquery(), 'latest_paid_payments', function ($join) {
                $join->on('latest_paid_payments.order_id', '=', 'orders.id');
            });

        $this->applyFinalOrderFilters($query, $filters);

        return $query;
    }

    private function baseFinalOrderItemsQuery(array $filters): Builder
    {
        $query = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoinSub($this->latestPaidPaymentsSubquery(), 'latest_paid_payments', function ($join) {
                $join->on('latest_paid_payments.order_id', '=', 'orders.id');
            });

        $this->applyFinalOrderFilters($query, $filters, includeProductFilters: false);

        return $query;
    }

    private function applyFinalOrderFilters(Builder $query, array $filters, bool $includeProductFilters = true): void
    {
        $query
            ->where('orders.customer_id', $filters['customer_id'])
            ->where('orders.payment_status', 'paid')
            ->whereNotNull('orders.paid_at')
            ->whereNotIn('orders.status', ['cancelled', 'returned'])
            ->whereNull('orders.deleted_at')
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('orders.paid_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('orders.paid_at', '<=', $date))
            ->when($filters['order_number'] ?? null, fn (Builder $query, string $value) => $query->where('orders.order_number', 'like', "%{$value}%"))
            ->when($filters['payment_method'] ?? null, fn (Builder $query, string $value) => $query->where('latest_paid_payments.method', $value))
            ->when($filters['order_status'] ?? null, fn (Builder $query, string $value) => $query->where('orders.status', $value))
            ->when($filters['min_amount'] ?? null, fn (Builder $query, $value) => $query->where('orders.total', '>=', $value))
            ->when($filters['max_amount'] ?? null, fn (Builder $query, $value) => $query->where('orders.total', '<=', $value));

        if (! $includeProductFilters) {
            return;
        }

        $query
            ->when($filters['product_search'] ?? null, function (Builder $query, string $value) {
                $query->whereExists(function ($subquery) use ($value) {
                    $subquery->selectRaw('1')
                        ->from('order_items')
                        ->whereColumn('order_items.order_id', 'orders.id')
                        ->where(function ($subquery) use ($value) {
                            $subquery->where('order_items.product_name', 'like', "%{$value}%")
                                ->orWhere('order_items.sku', 'like', "%{$value}%");
                        });
                });
            })
            ->when($filters['product_id'] ?? null, function (Builder $query, int $productId) {
                $query->whereExists(function ($subquery) use ($productId) {
                    $subquery->selectRaw('1')
                        ->from('order_items')
                        ->whereColumn('order_items.order_id', 'orders.id')
                        ->where('order_items.product_id', $productId);
                });
            });
    }

    private function applyItemFilters(Builder $query, array $filters): void
    {
        $query
            ->when($filters['product_search'] ?? null, function (Builder $query, string $value) {
                $query->where(function (Builder $query) use ($value) {
                    $query->where('order_items.product_name', 'like', "%{$value}%")
                        ->orWhere('order_items.sku', 'like', "%{$value}%");
                });
            })
            ->when($filters['product_id'] ?? null, fn (Builder $query, int $productId) => $query->where('order_items.product_id', $productId));
    }

    private function applyOrderSort(Builder $query, array $filters): void
    {
        $field = $filters['order_sort_field'] ?? null;
        $direction = ($filters['sort_order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        if ($field && isset(self::ORDER_SORT_MAP[$field])) {
            $query->orderBy(self::ORDER_SORT_MAP[$field], $direction);
        } else {
            $query->orderByDesc('orders.paid_at');
        }

        $query->orderByDesc('orders.id');
    }

    private function applyProductSort(Builder $query, array $filters): void
    {
        $field = $filters['product_sort_field'] ?? 'net_amount';
        $direction = ($filters['sort_order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        $query->orderBy(self::PRODUCT_SORT_MAP[$field] ?? 'net_amount', $direction)
            ->orderByDesc('last_purchased_at');
    }

    private function applyPaymentSort(Builder $query, array $filters): void
    {
        $field = $filters['payment_sort_field'] ?? null;
        $direction = ($filters['sort_order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        if ($field && isset(self::PAYMENT_SORT_MAP[$field])) {
            $query->orderBy(self::PAYMENT_SORT_MAP[$field], $direction);
        } else {
            $query->orderByDesc('payments.paid_at');
        }

        $query->orderByDesc('payments.id');
    }

    private function orderTimeline(array $filters): array
    {
        $select = $this->dateBucket($filters['chart_grouping']);

        return (clone $this->baseFinalOrdersQuery($filters))
            ->selectRaw("{$select} as label")
            ->selectRaw('SUM(orders.total) as total_amount')
            ->selectRaw('COUNT(DISTINCT orders.id) as orders_count')
            ->toBase()
            ->groupBy('label')
            ->orderBy('label')
            ->get()
            ->map(fn ($row) => [
                'label' => (string) $row->label,
                'total_amount' => (float) $row->total_amount,
                'orders_count' => (int) $row->orders_count,
            ])
            ->all();
    }

    private function itemTimeline(array $filters): array
    {
        $select = $this->dateBucket($filters['chart_grouping']);

        return $this->baseFinalOrderItemsQuery($filters)
            ->tap(fn (Builder $query) => $this->applyItemFilters($query, $filters))
            ->selectRaw("{$select} as label")
            ->selectRaw('SUM(order_items.quantity) as quantity')
            ->toBase()
            ->groupBy('label')
            ->orderBy('label')
            ->get()
            ->map(fn ($row) => [
                'label' => (string) $row->label,
                'quantity' => (int) $row->quantity,
            ])
            ->all();
    }

    private function topProducts(array $filters, string $sort): array
    {
        return $this->baseFinalOrderItemsQuery($filters)
            ->tap(fn (Builder $query) => $this->applyItemFilters($query, $filters))
            ->selectRaw('COALESCE(order_items.product_name, CONCAT("product-", order_items.product_id)) as label')
            ->selectRaw('SUM(order_items.quantity) as quantity_purchased')
            ->selectRaw('SUM(order_items.total_price) as net_amount')
            ->toBase()
            ->groupBy('order_items.product_id', 'order_items.product_name', 'order_items.sku')
            ->orderByDesc($sort === 'quantity_purchased' ? 'quantity_purchased' : 'net_amount')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'label' => (string) $row->label,
                'quantity_purchased' => (int) $row->quantity_purchased,
                'net_amount' => (float) $row->net_amount,
            ])
            ->all();
    }

    private function paymentMethodShares(array $filters): array
    {
        return (clone $this->baseFinalOrdersQuery($filters))
            ->selectRaw('COALESCE(latest_paid_payments.method, "unknown") as method')
            ->selectRaw('SUM(orders.total) as total_amount')
            ->selectRaw('COUNT(DISTINCT orders.id) as orders_count')
            ->toBase()
            ->groupBy('method')
            ->orderByDesc('total_amount')
            ->get()
            ->map(fn ($row) => [
                'method' => (string) $row->method,
                'total_amount' => (float) $row->total_amount,
                'orders_count' => (int) $row->orders_count,
            ])
            ->all();
    }

    private function averageDaysBetweenOrders(array $filters): ?float
    {
        $dates = (clone $this->baseFinalOrdersQuery($filters))
            ->orderBy('orders.paid_at')
            ->pluck('orders.paid_at')
            ->filter()
            ->values();

        if ($dates->count() < 2) {
            return null;
        }

        $totalDays = 0;
        for ($i = 1; $i < $dates->count(); $i++) {
            $totalDays += \Illuminate\Support\Carbon::parse($dates[$i - 1])->diffInDays(\Illuminate\Support\Carbon::parse($dates[$i]));
        }

        return round($totalDays / ($dates->count() - 1), 1);
    }

    private function dateBucket(string $grouping): string
    {
        return match ($grouping) {
            'week' => 'YEARWEEK(orders.paid_at, 3)',
            'month' => 'DATE_FORMAT(orders.paid_at, "%Y-%m")',
            default => 'DATE(orders.paid_at)',
        };
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

    private function baseAllFinalOrdersQuery(int $customerId): Builder
    {
        return Order::query()
            ->where('orders.customer_id', $customerId)
            ->where('orders.payment_status', 'paid')
            ->whereNotNull('orders.paid_at')
            ->whereNotIn('orders.status', ['cancelled', 'returned'])
            ->whereNull('orders.deleted_at');
    }

    private function finalPurchaseDates(int $customerId)
    {
        return $this->baseAllFinalOrdersQuery($customerId)
            ->orderBy('orders.paid_at')
            ->pluck('orders.paid_at')
            ->filter()
            ->values();
    }

    private function productPurchasePatterns(int $customerId): array
    {
        if (! $customerId) {
            return [];
        }

        $rows = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->leftJoin('products', 'products.id', '=', 'order_items.product_id')
            ->where('orders.customer_id', $customerId)
            ->where('orders.payment_status', 'paid')
            ->whereNotNull('orders.paid_at')
            ->whereNotIn('orders.status', ['cancelled', 'returned'])
            ->whereNull('orders.deleted_at')
            ->select([
                'order_items.product_id',
                'order_items.product_variant_id',
                'order_items.product_name',
                'order_items.sku',
                'order_items.quantity',
                'order_items.total_price',
                'orders.id as order_id',
                'orders.paid_at',
                'products.slug as product_slug',
                'products.main_image as image_url',
                'products.status as product_status',
                'products.deleted_at as product_deleted_at',
            ])
            ->orderBy('orders.paid_at')
            ->get();

        return $rows
            ->groupBy(fn ($row) => implode('|', [
                $row->product_id ?: 'snapshot',
                $row->product_variant_id ?: 'no-variant',
                $row->sku ?: '',
                $row->product_name ?: '',
            ]))
            ->map(function ($items, string $key) {
                $dates = $items->pluck('paid_at')->map(fn ($date) => \Illuminate\Support\Carbon::parse($date)->startOfDay())->unique(fn ($date) => $date->toDateString())->values();
                $intervals = [];

                for ($i = 1; $i < $dates->count(); $i++) {
                    $intervals[] = $dates[$i - 1]->diffInDays($dates[$i]);
                }

                $first = $items->first();
                $lastDate = $dates->last();
                $averageInterval = count($intervals) ? round(array_sum($intervals) / count($intervals), 1) : null;

                return [
                    'group_key' => $key,
                    'product_id' => $first->product_id,
                    'product_variant_id' => $first->product_variant_id,
                    'product_name' => $first->product_name,
                    'product_sku' => $first->sku,
                    'image_url' => $first->image_url,
                    'orders_count' => $items->pluck('order_id')->unique()->count(),
                    'quantity_purchased' => (int) $items->sum('quantity'),
                    'net_amount' => (float) $items->sum('total_price'),
                    'first_purchased_at' => $dates->first()?->toDateString(),
                    'last_purchased_at' => $lastDate?->toDateString(),
                    'days_since_last_purchase' => $lastDate ? (int) $lastDate->diffInDays(now()) : null,
                    'average_interval_days' => $averageInterval,
                    'purchase_dates_count' => $dates->count(),
                    'is_deleted' => $first->product_id === null || $first->product_deleted_at !== null,
                    'is_active_product' => $first->product_id && $first->product_deleted_at === null && ($first->product_status === null || $first->product_status === 'active'),
                    'product_url' => $first->product_id && ! $first->product_deleted_at && $first->product_slug ? route('admin.products.show', $first->product_slug, false) : null,
                ];
            })
            ->values()
            ->all();
    }

    private function withOverdueStatus(array $item): array
    {
        $ratio = $item['average_interval_days'] > 0
            ? round($item['days_since_last_purchase'] / $item['average_interval_days'], 2)
            : 0;

        [$label, $severity] = match (true) {
            $ratio < 0.8 => ['هنوز زود است', 'secondary'],
            $ratio <= 1.2 => ['زمان خرید نزدیک است', 'info'],
            $ratio <= 1.8 => ['احتمال نیاز به خرید مجدد', 'warn'],
            default => ['از زمان معمول خرید گذشته است', 'danger'],
        };

        return [
            ...$item,
            'overdue_ratio' => $ratio,
            'reminder_status' => $label,
            'severity' => $severity,
        ];
    }

    private function emptyStatistics(): array
    {
        return [
            'total_spent' => 0,
            'orders_count' => 0,
            'items_count' => 0,
            'products_count' => 0,
            'discount_total' => 0,
            'shipping_total' => 0,
            'average_order_value' => 0,
            'last_purchase_at' => null,
            'first_purchase_at' => null,
        ];
    }

    private function emptyPaginator(array $filters): LengthAwarePaginator
    {
        return new LengthAwarePaginator([], 0, $filters['per_page'], $filters['page']);
    }
}
