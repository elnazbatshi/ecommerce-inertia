<?php

namespace App\Http\Requests;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerPurchaseLedgerFilterRequest extends FormRequest
{
    public const ACTIVE_VIEWS = ['orders', 'products', 'payments', 'analytics', 'timeline'];
    public const PER_PAGE = [10, 25, 50, 100];
    public const CHART_GROUPINGS = ['day', 'week', 'month'];
    public const QUICK_RANGES = ['today', 'last_7_days', 'last_30_days', 'last_3_months', 'last_6_months', 'current_year', 'all'];

    public const ORDER_SORT_FIELDS = [
        'order_number',
        'created_at',
        'paid_at',
        'items_count',
        'subtotal',
        'discount',
        'shipping_amount',
        'total_amount',
        'status',
    ];

    public const PRODUCT_SORT_FIELDS = [
        'product_name',
        'product_sku',
        'quantity_purchased',
        'orders_count',
        'gross_amount',
        'discount_amount',
        'net_amount',
        'average_unit_price',
        'first_purchased_at',
        'last_purchased_at',
    ];

    public const PAYMENT_SORT_FIELDS = [
        'order_number',
        'method',
        'amount',
        'transaction_id',
        'reference_id',
        'paid_at',
        'status',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'customer_search' => ['nullable', 'string', 'max:255'],
            'active_view' => ['nullable', Rule::in(self::ACTIVE_VIEWS)],
            'chart_grouping' => ['nullable', Rule::in(self::CHART_GROUPINGS)],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', Rule::in(self::PER_PAGE)],
            'timeline_page' => ['nullable', 'integer', 'min:1'],
            'timeline_per_page' => ['nullable', 'integer', Rule::in([10, 20, 50])],
            'quick_range' => ['nullable', Rule::in(self::QUICK_RANGES)],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'order_number' => ['nullable', 'string', 'max:255'],
            'product_search' => ['nullable', 'string', 'max:255'],
            'product_id' => ['nullable', 'integer'],
            'payment_method' => ['nullable', Rule::in(Payment::METHODS)],
            'order_status' => ['nullable', Rule::in(array_diff(Order::STATUSES, ['cancelled', 'returned']))],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'max_amount' => ['nullable', 'numeric', 'gte:min_amount'],
            'sort_order' => ['nullable', Rule::in(['asc', 'desc'])],
            'order_sort_field' => ['nullable', Rule::in(self::ORDER_SORT_FIELDS)],
            'product_sort_field' => ['nullable', Rule::in(self::PRODUCT_SORT_FIELDS)],
            'payment_sort_field' => ['nullable', Rule::in(self::PAYMENT_SORT_FIELDS)],
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
            'customer_id' => $validated['customer_id'] ?? null,
            'customer_search' => $validated['customer_search'] ?? null,
            'active_view' => $validated['active_view'] ?? 'orders',
            'chart_grouping' => $validated['chart_grouping'] ?? 'day',
            'page' => (int) ($validated['page'] ?? 1),
            'per_page' => (int) ($validated['per_page'] ?? 25),
            'timeline_page' => (int) ($validated['timeline_page'] ?? 1),
            'timeline_per_page' => (int) ($validated['timeline_per_page'] ?? 10),
            'quick_range' => $quickRange,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'order_number' => $validated['order_number'] ?? null,
            'product_search' => $validated['product_search'] ?? null,
            'product_id' => $validated['product_id'] ?? null,
            'payment_method' => $validated['payment_method'] ?? null,
            'order_status' => $validated['order_status'] ?? null,
            'min_amount' => $validated['min_amount'] ?? null,
            'max_amount' => $validated['max_amount'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 'desc',
            'order_sort_field' => $validated['order_sort_field'] ?? null,
            'product_sort_field' => $validated['product_sort_field'] ?? 'net_amount',
            'payment_sort_field' => $validated['payment_sort_field'] ?? null,
        ];
    }

    private function resolveDateRange(?string $quickRange, ?string $dateFrom, ?string $dateTo): array
    {
        return match ($quickRange) {
            'today' => [now()->toDateString(), now()->toDateString()],
            'last_7_days' => [now()->subDays(6)->toDateString(), now()->toDateString()],
            'last_30_days' => [now()->subDays(29)->toDateString(), now()->toDateString()],
            'last_3_months' => [now()->subMonthsNoOverflow(3)->toDateString(), now()->toDateString()],
            'last_6_months' => [now()->subMonthsNoOverflow(6)->toDateString(), now()->toDateString()],
            'current_year' => [now()->startOfYear()->toDateString(), now()->endOfYear()->toDateString()],
            'all' => [null, null],
            default => [$dateFrom, $dateTo],
        };
    }
}
