<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Page extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'featured_image',
        'status',
        'published_at',
        'template',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'canonical_url',
        'seo_index',
        'seo_follow',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'meta_keywords' => 'array',
        'seo_index' => 'boolean',
        'seo_follow' => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function media(): MorphToMany
    {
        return $this->morphToMany(Media::class, 'mediable', 'mediables')
            ->withPivot(['collection', 'sort_order', 'is_featured', 'custom_properties'])
            ->withTimestamps();
    }

    public function featuredImage(): MorphToMany
    {
        return $this->media()->wherePivot('collection', 'page_featured')->orderByPivot('sort_order');
    }
}
