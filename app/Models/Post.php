<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Post extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'post_category_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'featured_image',
        'status',
        'published_at',
        'view_count',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'canonical_url',
        'seo_index',
        'seo_follow',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'view_count' => 'integer',
        'meta_keywords' => 'array',
        'seo_index' => 'boolean',
        'seo_follow' => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(PostCategory::class, 'post_category_id');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(PostTag::class, 'post_post_tag');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    public function media(): MorphToMany
    {
        return $this->morphToMany(Media::class, 'mediable', 'mediables')
            ->withPivot(['collection', 'sort_order', 'is_featured', 'custom_properties'])
            ->withTimestamps();
    }

    public function featuredImage(): MorphToMany
    {
        return $this->media()->wherePivot('collection', 'post_featured')->orderByPivot('sort_order');
    }
}
