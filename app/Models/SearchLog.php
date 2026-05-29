<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchLog extends Model
{
    protected $fillable = [
        'query',
        'type',
        'matched_id',
        'matched_type',
        'results_count',
        'user_id',
        'ip_address',
        'user_agent',
        'searched_at',
    ];

    protected $casts = [
        'searched_at' => 'datetime',
        'results_count' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
