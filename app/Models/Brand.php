<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Brand extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'logo',
        'is_active',
        'description',
        'content',
        'featured_image',
        'cover_image',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'canonical_url',
        'seo_index',
        'seo_follow',
    ];

    protected $casts = [
        'seo_index' => 'boolean',
        'seo_follow' => 'boolean',
        'is_active' => 'boolean',
        'meta_keywords' => 'array',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function media(): MorphToMany
    {
        return $this->morphToMany(Media::class, 'mediable', 'mediables')
            ->withPivot(['collection', 'sort_order', 'is_featured', 'custom_properties'])
            ->withTimestamps();
    }

    public function logo(): MorphToMany
    {
        return $this->media()->wherePivot('collection', 'brand_logo')->orderByPivot('sort_order');
    }

    public function coverImage(): MorphToMany
    {
        return $this->media()->wherePivot('collection', 'brand_cover')->orderByPivot('sort_order');
    }

    public function featuredImage(): MorphToMany
    {
        return $this->media()->wherePivot('collection', 'brand_featured')->orderByPivot('sort_order');
    }
}
