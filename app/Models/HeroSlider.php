<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class HeroSlider extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'subtitle',
        'eyebrow_text',
        'description',
        'background_media_id',
        'foreground_media_id',
        'overlay_opacity',
        'button_primary_text',
        'button_primary_url',
        'button_secondary_text',
        'button_secondary_url',
        'badge_text',
        'badge_url',
        'stat_1_label',
        'stat_1_value',
        'stat_2_label',
        'stat_2_value',
        'stat_3_label',
        'stat_3_value',
        'text_color',
        'accent_color',
        'button_color',
        'layout',
        'placement',
        'sort_order',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'overlay_opacity' => 'decimal:2',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function backgroundMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'background_media_id');
    }

    public function foregroundMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'foreground_media_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderByDesc('id');
    }

    public function scopeCurrentlyVisible(Builder $query): Builder
    {
        $now = Carbon::now();

        return $query
            ->active()
            ->where(function (Builder $builder) use ($now) {
                $builder->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function (Builder $builder) use ($now) {
                $builder->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            });
    }
}
