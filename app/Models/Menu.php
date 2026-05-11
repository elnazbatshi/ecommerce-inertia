<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Menu extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'location',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public static function locations(): array
    {
        return [
            'header' => 'Header',
            'footer' => 'Footer',
            'mobile' => 'Mobile',
            'sidebar' => 'Sidebar',
            'topbar' => 'Top Bar',
        ];
    }

    public static function locationOptions(): array
    {
        return collect(self::locations())->map(fn ($label, $value) => ['label' => $label, 'value' => $value])->values()->all();
    }

    public function items()
    {
        return $this->hasMany(MenuItem::class)->orderBy('sort_order');
    }

    public function activeItems()
    {
        return $this->hasMany(MenuItem::class)
            ->where('is_active', true)
            ->orderBy('sort_order');
    }

    public function rootItems()
    {
        return $this->hasMany(MenuItem::class)
            ->whereNull('parent_id')
            ->orderBy('sort_order');
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }
}
