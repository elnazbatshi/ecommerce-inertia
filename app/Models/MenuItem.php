<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MenuItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'menu_id',
        'parent_id',
        'title',
        'type',
        'url',
        'route_name',
        'route_params',
        'target',
        'icon',
        'css_class',
        'rel',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'route_params' => 'array',
        'is_active' => 'boolean',
    ];

    public static function types(): array
    {
        return [
            ['label' => 'داخلی', 'value' => 'internal'],
            ['label' => 'خارجی', 'value' => 'external'],
        ];
    }

    public static function targets(): array
    {
        return [
            ['label' => 'همین پنجره', 'value' => '_self'],
            ['label' => 'پنجره جدید', 'value' => '_blank'],
        ];
    }

    public function menu()
    {
        return $this->belongsTo(Menu::class);
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function childrenRecursive()
    {
        return $this->children()->with('childrenRecursive');
    }
}
