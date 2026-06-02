<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class SearchSuggestion extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'type',
        'reference_id',
        'url',
        'keyword',
        'icon',
        'sort_order',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'sort_order' => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        $now = Carbon::now();

        return $query
            ->where('is_active', true)
            ->where(function (Builder $builder) use ($now) {
                $builder->whereNull('starts_at')->orWhere('starts_at', '<=', $now);
            })
            ->where(function (Builder $builder) use ($now) {
                $builder->whereNull('ends_at')->orWhere('ends_at', '>=', $now);
            });
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderByDesc('id');
    }

    public function resolveReference(): mixed
    {
        if (!$this->reference_id) {
            return null;
        }

        return match ($this->type) {
            'product' => Product::query()->find($this->reference_id),
            'category' => Category::query()->find($this->reference_id),
            'brand' => Brand::query()->find($this->reference_id),
            default => null,
        };
    }

    public function resolveUrl(): ?string
    {
        if ($this->type === 'custom') {
            return $this->url;
        }

        $reference = $this->resolveReference();

        if (!$reference) {
            return $this->url;
        }

        return match ($this->type) {
            'product' => "/products/{$reference->slug}",
            'category' => "/category/{$reference->slug}",
            'brand' => "/brand/{$reference->slug}",
            default => $this->url,
        };
    }
}
