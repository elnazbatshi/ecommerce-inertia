<?php

namespace App\Http\Requests;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductSalesLedgerFilterRequest extends FormRequest
{
    public const ACTIVE_VIEWS = ['transactions', 'products', 'chart'];
    public const PER_PAGE = [10, 25, 50, 100];
    public const SORT_FIELDS = [
        'paid_at',
        'order_number',
        'product_name',
        'product_sku',
        'quantity',
        'unit_price',
        'discount',
        'total_price',
        'customer_name',
        'transaction_id',
    ];
    public const PRODUCT_SORT_FIELDS = [
        'product_name',
        'product_sku',
        'quantity_sold',
        'orders_count',
        'gross_sales',
        'discount_total',
        'net_sales',
        'average_unit_price',
        'last_sold_at',
        'sales_share',
    ];
    public const CHART_GROUPINGS = ['day', 'week', 'month'];
    public const QUICK_RANGES = ['today', 'last_7_days', 'last_30_days', 'current_month', 'previous_month', 'all'];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'active_view' => ['nullable', Rule::in(self::ACTIVE_VIEWS)],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', Rule::in(self::PER_PAGE)],
            'sort_field' => ['nullable', Rule::in(self::SORT_FIELDS)],
            'product_sort_field' => ['nullable', Rule::in(self::PRODUCT_SORT_FIELDS)],
            'sort_order' => ['nullable', Rule::in(['asc', 'desc'])],
            'chart_grouping' => ['nullable', Rule::in(self::CHART_GROUPINGS)],
            'quick_range' => ['nullable', Rule::in(self::QUICK_RANGES)],
            'search' => ['nullable', 'string', 'max:255'],
            'order_number' => ['nullable', 'string', 'max:255'],
            'transaction_id' => ['nullable', 'string', 'max:255'],
            'product_query' => ['nullable', 'string', 'max:255'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'customer_query' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'order_status' => ['nullable', Rule::in(array_diff(Order::STATUSES, ['cancelled', 'returned']))],
            'payment_status' => ['nullable', Rule::in(['paid'])],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ];
    }

    public function messages(): array
    {
        return [
            'date_to.after_or_equal' => 'تاریخ پایان باید بعد از تاریخ شروع یا برابر با آن باشد.',
            'date_from.date' => 'تاریخ شروع معتبر نیست.',
            'date_to.date' => 'تاریخ پایان معتبر نیست.',
        ];
    }

    public function filters(): array
    {
        $validated = $this->validated();
        $quickRange = $validated['quick_range'] ?? null;
        [$dateFrom, $dateTo] = $this->resolveDateRange(
            $quickRange,
            $validated['date_from'] ?? null,
            $validated['date_to'] ?? null,
        );

        return [
            'active_view' => $validated['active_view'] ?? 'transactions',
            'page' => (int) ($validated['page'] ?? 1),
            'per_page' => (int) ($validated['per_page'] ?? 25),
            'sort_field' => $validated['sort_field'] ?? null,
            'product_sort_field' => $validated['product_sort_field'] ?? 'net_sales',
            'sort_order' => $validated['sort_order'] ?? 'desc',
            'chart_grouping' => $validated['chart_grouping'] ?? 'day',
            'quick_range' => $quickRange,
            'search' => $validated['search'] ?? null,
            'order_number' => $validated['order_number'] ?? null,
            'transaction_id' => $validated['transaction_id'] ?? null,
            'product_query' => $validated['product_query'] ?? null,
            'product_id' => $validated['product_id'] ?? null,
            'customer_query' => $validated['customer_query'] ?? null,
            'payment_method' => $validated['payment_method'] ?? null,
            'order_status' => $validated['order_status'] ?? null,
            'payment_status' => $validated['payment_status'] ?? null,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }

    private function resolveDateRange(?string $quickRange, ?string $dateFrom, ?string $dateTo): array
    {
        return match ($quickRange) {
            'today' => [now()->toDateString(), now()->toDateString()],
            'last_7_days' => [now()->subDays(6)->toDateString(), now()->toDateString()],
            'last_30_days' => [now()->subDays(29)->toDateString(), now()->toDateString()],
            'current_month' => [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()],
            'previous_month' => [now()->subMonthNoOverflow()->startOfMonth()->toDateString(), now()->subMonthNoOverflow()->endOfMonth()->toDateString()],
            'all' => [null, null],
            default => [$dateFrom, $dateTo],
        };
    }
}
