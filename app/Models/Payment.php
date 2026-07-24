<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;

    public const METHODS = ['online', 'card_to_card', 'cash', 'wallet'];
    public const STATUSES = ['pending', 'paid', 'failed', 'cancelled', 'refunded'];

    protected $fillable = [
        'order_id',
        'customer_id',
        'payment_method_id',
        'amount',
        'currency',
        'method',
        'gateway',
        'authority',
        'transaction_id',
        'reference_id',
        'status',
        'initiated_at',
        'paid_at',
        'verified_at',
        'failed_at',
        'error_code',
        'error_message',
        'request_payload',
        'response_payload',
        'callback_payload',
        'raw_response',
        'admin_note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'initiated_at' => 'datetime',
        'paid_at' => 'datetime',
        'verified_at' => 'datetime',
        'failed_at' => 'datetime',
        'request_payload' => 'array',
        'response_payload' => 'array',
        'callback_payload' => 'array',
        'raw_response' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        return $query->when($search, function (Builder $query, string $search) {
            $query->where('transaction_id', 'like', "%{$search}%")
                ->orWhere('reference_id', 'like', "%{$search}%")
                ->orWhereHas('order', fn (Builder $orderQuery) => $orderQuery->where('order_number', 'like', "%{$search}%"))
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
            ->when($filters['method'] ?? null, fn (Builder $query, string $method) => $query->where('method', $method))
            ->when($filters['gateway'] ?? null, fn (Builder $query, string $gateway) => $query->where('gateway', $gateway))
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '<=', $date));
    }
}
