<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MenuItem extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPES = ['custom', 'page', 'category', 'product', 'brand', 'post', 'external'];
    public const CHILDREN_SOURCES = ['categories', 'brands', 'products', 'posts', 'pages'];

    protected $fillable = [
        'menu_id',
        'parent_id',
        'title',
        'title_attribute',
        'type',
        'reference_id',
        'url',
        'route_name',
        'route_params',
        'target',
        'icon',
        'css_class',
        'rel',
        'depth',
        'sort_order',
        'is_active',
        'auto_children',
        'children_source',
    ];

    protected $casts = [
        'route_params' => 'array',
        'reference_id' => 'integer',
        'depth' => 'integer',
        'sort_order' => 'integer',
        'is_active' => 'boolean',
        'auto_children' => 'boolean',
    ];

    public static function types(): array
    {
        return [
            ['label' => 'لینک سفارشی', 'value' => 'custom'],
            ['label' => 'صفحه CMS', 'value' => 'page'],
            ['label' => 'دسته‌بندی', 'value' => 'category'],
            ['label' => 'محصول', 'value' => 'product'],
            ['label' => 'برند', 'value' => 'brand'],
            ['label' => 'مقاله', 'value' => 'post'],
            ['label' => 'لینک خارجی', 'value' => 'external'],
        ];
    }

    public static function targets(): array
    {
        return [
            ['label' => 'همین پنجره', 'value' => '_self'],
            ['label' => 'تب جدید', 'value' => '_blank'],
        ];
    }

    public static function childrenSources(): array
    {
        return [
            ['label' => 'دسته‌بندی‌ها', 'value' => 'categories'],
            ['label' => 'برندها', 'value' => 'brands'],
            ['label' => 'محصولات', 'value' => 'products'],
            ['label' => 'مقالات', 'value' => 'posts'],
            ['label' => 'صفحات', 'value' => 'pages'],
        ];
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function childrenRecursive(): HasMany
    {
        return $this->children()->with('childrenRecursive');
    }
}
