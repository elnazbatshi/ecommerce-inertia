<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'disk',
        'path',
        'filename',
        'original_name',
        'mime_type',
        'extension',
        'size',
        'width',
        'height',
        'alt',
        'title',
        'caption',
        'description',
        'metadata',
        'uploaded_by',
    ];

    protected $casts = [
        'size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'metadata' => 'array',
        'is_featured' => 'boolean',
    ];

    protected $appends = ['url'];

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function products(): MorphToMany
    {
        return $this->morphedByMany(Product::class, 'mediable', 'mediables')
            ->withPivot(['collection', 'sort_order', 'is_featured', 'custom_properties'])
            ->withTimestamps();
    }

    public function brands(): MorphToMany
    {
        return $this->morphedByMany(Brand::class, 'mediable', 'mediables')
            ->withPivot(['collection', 'sort_order', 'is_featured', 'custom_properties'])
            ->withTimestamps();
    }

    public function posts(): MorphToMany
    {
        return $this->morphedByMany(BlogPost::class, 'mediable', 'mediables')
            ->withPivot(['collection', 'sort_order', 'is_featured', 'custom_properties'])
            ->withTimestamps();
    }

    public function pages(): MorphToMany
    {
        return $this->morphedByMany(Page::class, 'mediable', 'mediables')
            ->withPivot(['collection', 'sort_order', 'is_featured', 'custom_properties'])
            ->withTimestamps();
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }
}
