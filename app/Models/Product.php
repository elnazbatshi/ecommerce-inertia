<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'canonical_url',
        'seo_index',
        'seo_follow',
        'description',
        'brand_id',
        'category_id',
        'material',
        'origin',
        'sku',
        'barcode',
        'price',
        'discount_price',
        'currency',
        'main_image',
        'status',
        'type',
        'is_original',
        'is_featured',
        'stock',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'discount_price' => 'decimal:2',
        'stock' => 'integer',
        'views' => 'integer',
        'is_original' => 'boolean',
        'is_featured' => 'boolean',
        'seo_index' => 'boolean',
        'seo_follow' => 'boolean',
        'meta_keywords' => 'array',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function inventoryLogs(): HasMany
    {
        return $this->hasMany(InventoryLog::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(ProductReview::class);
    }

    public function wishlistedByCustomers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'customer_wishlists')->withTimestamps();
    }

    public function relatedPosts(): BelongsToMany
    {
        return $this->belongsToMany(BlogPost::class, 'blog_post_product')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    public function blogPosts(): BelongsToMany
    {
        return $this->relatedPosts();
    }
    public function vehicles(): BelongsToMany
    {
        return $this->belongsToMany(Vehicle::class, 'product_vehicle')->withTimestamps();
    }

    public function media(): MorphToMany
    {
        return $this->morphToMany(Media::class, 'mediable', 'mediables')
            ->withPivot(['collection', 'sort_order', 'is_featured', 'custom_properties'])
            ->withTimestamps();
    }

    public function mainImage(): MorphToMany
    {
        return $this->media()->wherePivot('collection', 'product_main')->orderByPivot('sort_order');
    }

    public function galleryImages(): MorphToMany
    {
        return $this->media()->wherePivot('collection', 'product_gallery')->orderByPivot('sort_order');
    }

    public function scopeSearch(Builder $query, ?string $search): Builder
    {
        return $query->when($search, function (Builder $query, string $search) {
            $query->where(function (Builder $query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%");
            });
        });
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        $query
            ->when($filters['category_id'] ?? null, fn (Builder $query, int $id) => $query->where('category_id', $id))
            ->when($filters['brand_id'] ?? null, fn (Builder $query, int $id) => $query->where('brand_id', $id))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['type'] ?? null, fn (Builder $query, string $type) => $query->where('type', $type));

        switch ($filters['is_featured'] ?? null) {
            case '1':
                $query->where('is_featured', true);
                break;

            case '0':
                $query->where('is_featured', false);
                break;
        }

        return $query;
    }
}
