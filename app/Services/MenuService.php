<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Models\Vehicle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

class MenuService
{
    public function getByLocation(string $location): array
    {
        $menu = $this->menuForLocation($location);

        return [
            'location' => $location,
            'items' => $menu ? $this->buildTree($menu->rootItems) : $this->fallbackItems($location),
            'popular_brands' => $this->popularBrands(),
            'popular_vehicles' => $this->popularVehicles(),
            'quick_links' => $this->quickLinks(),
        ];
    }

    public function getMenuByLocation(string $location): array
    {
        return $this->getByLocation($location);
    }

    private function menuForLocation(string $location): ?Menu
    {
        $withItems = ['rootItems' => fn ($query) => $query
            ->where('is_active', true)
            ->with(['childrenRecursive' => fn ($query) => $query->where('is_active', true)]),
        ];

        $preferred = Menu::query()
            ->where('location', $location)
            ->where('slug', "{$location}-menu")
            ->where('is_active', true)
            ->with($withItems)
            ->first();

        if ($preferred) {
            return $preferred;
        }

        return Menu::query()
            ->where('location', $location)
            ->where('is_active', true)
            ->latest('updated_at')
            ->latest('id')
            ->with($withItems)
            ->first();
    }

    public function buildTree(Collection $items): array
    {
        return $items
            ->filter(fn (MenuItem $item) => $item->is_active)
            ->map(fn (MenuItem $item) => $this->mapItem($item))
            ->values()
            ->all();
    }

    public function resolveUrl(MenuItem $item): string
    {
        if (in_array($item->type, ['custom', 'external'], true)) {
            return $this->cleanUrl($item->url);
        }

        $url = match ($item->type) {
            'page' => $this->prefixedSlug('/page', $item),
            'category' => $this->prefixedSlug('/category', $item),
            'product' => $this->prefixedSlug('/products', $item),
            'brand' => $this->prefixedSlug('/brand', $item),
            'post' => $this->prefixedSlug('/blog', $item),
            default => null,
        };

        if ($url) {
            return $url;
        }

        if ($item->route_name && Route::has($item->route_name)) {
            return route($item->route_name, $item->route_params ?? [], false);
        }

        return '#';
    }

    private function mapItem(MenuItem $item): array
    {
        $children = collect($this->buildTree($item->childrenRecursive));
        $autoChildren = collect($this->autoChildren($item));
        $existingKeys = $children
            ->map(fn (array $child) => "{$child['type']}:".($child['reference_id'] ?? $child['url'] ?? $child['id']))
            ->filter()
            ->all();

        $autoChildren = $autoChildren->reject(function (array $child) use ($existingKeys) {
            $key = "{$child['type']}:".($child['reference_id'] ?? $child['url'] ?? $child['id']);

            return in_array($key, $existingKeys, true);
        });

        $slug = $this->slugForItem($item);

        return [
            'id' => $item->id,
            'reference_id' => $item->reference_id,
            'slug' => $slug,
            'title' => $item->title,
            'url' => $this->resolveUrl($item),
            'type' => $item->type,
            'target' => $item->target,
            'icon' => $item->icon,
            'css_class' => $item->css_class,
            'rel' => $item->rel,
            'auto_children' => $item->auto_children,
            'children_source' => $item->children_source,
            'children' => $children->merge($autoChildren)->values()->all(),
        ];
    }

    private function autoChildren(MenuItem $item): array
    {
        if (! $item->auto_children || ! $item->children_source) {
            return [];
        }

        return match ($item->children_source) {
            'categories' => $this->categoryChildren($item),
            'brands' => $this->brandChildren(),
            'products' => $this->productChildren($item),
            'posts' => $this->postChildren(),
            'pages' => $this->pageChildren(),
            default => [],
        };
    }

    private function categoryChildren(MenuItem $item): array
    {
        if (! Schema::hasTable('categories')) {
            return [];
        }

        $parentId = $item->type === 'category' ? $item->reference_id : null;

        return Category::query()
            ->when($parentId, fn (Builder $query) => $query->where('parent_id', $parentId), fn (Builder $query) => $query->whereNull('parent_id'))
            ->when(Schema::hasColumn('categories', 'is_active'), fn (Builder $query) => $query->where('is_active', true))
            ->orderBy(Schema::hasColumn('categories', 'sort_order') ? 'sort_order' : 'name')
            ->orderBy('name')
            ->limit(24)
            ->get(['id', 'name', 'slug'])
            ->map(fn (Category $category) => $this->autoItem($category->id, $category->slug, $category->name, "/category/{$category->slug}", 'category'))
            ->values()
            ->all();
    }

    private function brandChildren(): array
    {
        if (! Schema::hasTable('brands')) {
            return [];
        }

        return Brand::query()
            ->when(Schema::hasColumn('brands', 'is_active'), fn (Builder $query) => $query->where('is_active', true))
            ->orderBy('name')
            ->limit(24)
            ->get(['id', 'name', 'slug'])
            ->map(fn (Brand $brand) => $this->autoItem($brand->id, $brand->slug, $brand->name, "/brand/{$brand->slug}", 'brand'))
            ->values()
            ->all();
    }

    private function productChildren(MenuItem $item): array
    {
        if (! Schema::hasTable('products')) {
            return [];
        }

        return Product::query()
            ->when($item->type === 'category' && $item->reference_id, fn (Builder $query) => $query->where('category_id', $item->reference_id))
            ->when($item->type === 'brand' && $item->reference_id, fn (Builder $query) => $query->where('brand_id', $item->reference_id))
            ->when(Schema::hasColumn('products', 'status'), fn (Builder $query) => $query->where('status', 'active'))
            ->when(Schema::hasColumn('products', 'is_featured'), fn (Builder $query) => $query->orderByDesc('is_featured'))
            ->latest('id')
            ->limit(12)
            ->get(['id', 'name', 'slug'])
            ->map(fn (Product $product) => $this->autoItem($product->id, $product->slug, $product->name, "/products/{$product->slug}", 'product'))
            ->values()
            ->all();
    }

    private function postChildren(): array
    {
        if (! Schema::hasTable('posts')) {
            return [];
        }

        return Post::query()
            ->when(Schema::hasColumn('posts', 'status'), fn (Builder $query) => $query->where('status', 'published'))
            ->latest('published_at')
            ->latest('id')
            ->limit(12)
            ->get(['id', 'title', 'slug'])
            ->map(fn (Post $post) => $this->autoItem($post->id, $post->slug, $post->title, "/blog/{$post->slug}", 'post'))
            ->values()
            ->all();
    }

    private function pageChildren(): array
    {
        if (! Schema::hasTable('pages')) {
            return [];
        }

        return Page::query()
            ->when(Schema::hasColumn('pages', 'status'), fn (Builder $query) => $query->where('status', 'published'))
            ->orderBy('title')
            ->limit(24)
            ->get(['id', 'title', 'slug'])
            ->map(fn (Page $page) => $this->autoItem($page->id, $page->slug, $page->title, "/page/{$page->slug}", 'page'))
            ->values()
            ->all();
    }

    private function autoItem(int $id, string $slug, string $title, string $url, string $type): array
    {
        return [
            'id' => "auto-{$type}-{$id}",
            'reference_id' => $id,
            'slug' => $slug,
            'title' => $title,
            'url' => $url,
            'type' => $type,
            'target' => '_self',
            'icon' => null,
            'css_class' => null,
            'rel' => null,
            'children' => [],
        ];
    }

    private function slugForItem(MenuItem $item): ?string
    {
        $params = $item->route_params ?? [];

        return $params['slug'] ?? ($item->type === 'custom' ? trim((string) $item->url, '/') : null);
    }

    private function prefixedSlug(string $prefix, MenuItem $item): string
    {
        $params = $item->route_params ?? [];
        $slug = $params['slug'] ?? $item->url;

        return $slug ? $prefix.'/'.ltrim((string) $slug, '/') : '#';
    }

    private function cleanUrl(?string $url): string
    {
        if (! $url) {
            return '#';
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://') || str_starts_with($url, 'mailto:') || str_starts_with($url, 'tel:')) {
            return $url;
        }

        return '/'.ltrim($url, '/');
    }

    private function fallbackItems(string $location): array
    {
        if ($location !== 'header') {
            return [];
        }

        return [
            ['id' => 'fallback-home', 'slug' => '', 'title' => 'صفحه اصلی', 'url' => '/', 'type' => 'custom', 'target' => '_self', 'icon' => null, 'css_class' => null, 'rel' => null, 'children' => []],
            ['id' => 'fallback-blog', 'slug' => 'blog', 'title' => 'وبلاگ', 'url' => '/blog', 'type' => 'custom', 'target' => '_self', 'icon' => null, 'css_class' => null, 'rel' => null, 'children' => []],
        ];
    }

    private function popularBrands(): array
    {
        if (! Schema::hasTable('brands')) {
            return [];
        }

        return Brand::query()
            ->when(Schema::hasColumn('brands', 'is_active'), fn (Builder $query) => $query->where('is_active', true))
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'name', 'slug'])
            ->map(fn (Brand $brand) => [
                'id' => $brand->id,
                'title' => $brand->name,
                'slug' => $brand->slug,
                'url' => "/brand/{$brand->slug}",
            ])
            ->values()
            ->all();
    }

    private function popularVehicles(): array
    {
        if (! Schema::hasTable('vehicles')) {
            return [];
        }

        return Vehicle::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->limit(8)
            ->get()
            ->map(fn (Vehicle $vehicle) => [
                'id' => $vehicle->id,
                'title' => trim(implode(' ', array_filter([$vehicle->brand, $vehicle->model, $vehicle->trim]))),
                'slug' => $vehicle->slug,
            ])
            ->values()
            ->all();
    }

    private function quickLinks(): array
    {
        return [
            ['title' => 'راهنمای انتخاب روغن موتور', 'url' => '/blog?topic=engine-oil'],
            ['title' => 'سؤال از کارشناس', 'url' => '/page/contact-expert'],
        ];
    }
}
