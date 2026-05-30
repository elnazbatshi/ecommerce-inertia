<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    public const STATUSES = ['pending', 'processing', 'shipped', 'delivered', 'cancelled', 'returned'];
    public const PAYMENT_STATUSES = ['unpaid', 'paid', 'failed', 'refunded'];

    protected $fillable = [
        'order_number',
        'customer_id',
        'address_id',
        'shipping_method_id',
        'payment_method_id',
        'shipping_receiver_name',
        'shipping_receiver_phone',
        'shipping_province_name',
        'shipping_city_name',
        'shipping_address',
        'shipping_postal_code',
        'shipping_plaque',
        'shipping_unit',
        'shipping_method_name',
        'payment_method_name',
        'status',
        'payment_status',
        'subtotal',
        'discount_total',
        'shipping_cost',
        'tax_total',
        'total',
        'customer_note',
        'admin_note',
        'paid_at',
        'shipped_at',
        'delivered_at',
        'cancelled_at',
        'inventory_reduced_at',
        'inventory_returned_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'tax_total' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'inventory_reduced_at' => 'datetime',
        'inventory_returned_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class)->withTrashed();
    }

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        return $query->when($search, function (Builder $query, string $search) {
            $query->where('order_number', 'like', "%{$search}%")
                ->orWhereHas('customer', function (Builder $customerQuery) use ($search) {
                    $customerQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
        });
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['payment_status'] ?? null, fn (Builder $query, string $status) => $query->where('payment_status', $status))
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '<=', $date));
    }
}
