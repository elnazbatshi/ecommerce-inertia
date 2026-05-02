<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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
        'amount',
        'method',
        'gateway',
        'transaction_id',
        'reference_id',
        'status',
        'paid_at',
        'raw_response',
        'admin_note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
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
}
