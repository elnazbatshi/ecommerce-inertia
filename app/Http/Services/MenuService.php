<?php

namespace App\Http\Services;

use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;

class MenuService
{
    public function getMenuByLocation(string $location): array
    {
        $menu = Menu::where('location', $location)
            ->where('is_active', true)
            ->with(['rootItems' => fn ($query) => $query->where('is_active', true)->with('childrenRecursive')])
            ->first();

        if (! $menu) {
            return [];
        }

        return [
            'name' => $menu->name,
            'slug' => $menu->slug,
            'location' => $menu->location,
            'items' => $this->buildTree($menu->rootItems),
        ];
    }

    public function buildTree($items): array
    {
        return collect($items)->map(function (MenuItem $item) {
            return [
                'id' => $item->id,
                'title' => $item->title,
                'type' => $item->type,
                'url' => $this->normalizeUrl($item),
                'icon' => $item->icon,
                'target' => $item->target,
                'rel' => $item->rel,
                'css_class' => $item->css_class,
                'is_active' => $item->is_active,
                'children' => $this->buildTree($item->childrenRecursive),
            ];
        })->values()->all();
    }

    private function normalizeUrl(MenuItem $item): ?string
    {
        if ($item->type === 'external') {
            return $item->url;
        }

        if ($item->route_name && Route::has($item->route_name)) {
            return route($item->route_name, $item->route_params ?? []);
        }

        if ($item->url) {
            return url($item->url);
        }

        return null;
    }
}
