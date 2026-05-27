<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMenuRequest;
use App\Http\Requests\UpdateMenuRequest;
use App\Http\Services\SlugService;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use App\Services\MenuService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class MenuController extends Controller
{
    public function index(): Response
    {
        return $this->builder();
    }

    public function builder(?Menu $menu = null): Response
    {
        $selectedMenu = $menu ?? Menu::query()->orderBy('name')->first();
        $selectedMenu?->load(['rootItems' => fn ($query) => $query->with('childrenRecursive')]);

        return Inertia::render('Menus/Builder', [
            'menus' => Menu::query()->orderBy('name')->get(['id', 'name', 'slug', 'location', 'is_active']),
            'menu' => $selectedMenu ? $this->serializeMenu($selectedMenu) : null,
            'locationOptions' => Menu::locationOptions(),
            'typeOptions' => MenuItem::types(),
            'targetOptions' => MenuItem::targets(),
            'childrenSourceOptions' => MenuItem::childrenSources(),
            'linkOptions' => $this->linkOptions(),
        ]);
    }

    public function store(StoreMenuRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $data['slug']
            ? app(SlugService::class)->unique(Menu::class, $data['slug'])
            : app(SlugService::class)->unique(Menu::class, $data['name']);

        $menu = Menu::create($data);

        return Redirect::route('admin.menus.builder', $menu)->with('success', 'منو با موفقیت ساخته شد.');
    }

    public function update(UpdateMenuRequest $request, Menu $menu): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $data['slug']
            ? app(SlugService::class)->unique(Menu::class, $data['slug'], $menu->id)
            : app(SlugService::class)->unique(Menu::class, $data['name'], $menu->id);

        $menu->update($data);

        return Redirect::route('admin.menus.builder', $menu)->with('success', 'منو با موفقیت به‌روزرسانی شد.');
    }

    public function destroy(Menu $menu): RedirectResponse
    {
        $menu->delete();

        return Redirect::route('admin.menus.index')->with('success', 'منو حذف شد.');
    }

    public function show(Menu $menu, MenuService $menuService): JsonResponse
    {
        $menu->load(['rootItems' => fn ($query) => $query->with('childrenRecursive')]);

        return response()->json([
            'menu' => $this->serializeMenu($menu),
            'tree' => $menuService->buildTree($menu->rootItems),
        ]);
    }

    public function location(string $location, MenuService $menuService): JsonResponse
    {
        return response()->json($menuService->getMenuByLocation($location));
    }

    private function serializeMenu(Menu $menu): array
    {
        return [
            'id' => $menu->id,
            'name' => $menu->name,
            'slug' => $menu->slug,
            'location' => $menu->location,
            'description' => $menu->description,
            'is_active' => $menu->is_active,
            'items' => $this->mapItems($menu->rootItems),
        ];
    }

    private function mapItems($items): array
    {
        return $items->map(fn (MenuItem $item) => [
            'id' => $item->id,
            'parent_id' => $item->parent_id,
            'title' => $item->title,
            'title_attribute' => $item->title_attribute,
            'type' => $item->type,
            'reference_id' => $item->reference_id,
            'url' => $item->url,
            'route_name' => $item->route_name,
            'route_params' => $item->route_params,
            'target' => $item->target,
            'icon' => $item->icon,
            'css_class' => $item->css_class,
            'rel' => $item->rel,
            'depth' => $item->depth,
            'sort_order' => $item->sort_order,
            'is_active' => $item->is_active,
            'auto_children' => $item->auto_children,
            'children_source' => $item->children_source,
            'children' => $this->mapItems($item->childrenRecursive),
        ])->values()->all();
    }

    private function linkOptions(): array
    {
        return [
            'pages' => Page::query()->orderBy('title')->limit(100)->get(['id', 'title', 'slug'])
                ->map(fn (Page $page) => $this->option($page->id, $page->title, $page->slug, '/page/'.$page->slug))->values()->all(),
            'categories' => Category::query()->orderBy('name')->limit(100)->get(['id', 'name', 'slug'])
                ->map(fn (Category $category) => $this->option($category->id, $category->name, $category->slug, '/category/'.$category->slug))->values()->all(),
            'products' => Product::query()->orderBy('name')->limit(100)->get(['id', 'name', 'slug'])
                ->map(fn (Product $product) => $this->option($product->id, $product->name, $product->slug, '/products/'.$product->slug))->values()->all(),
            'brands' => Brand::query()->orderBy('name')->limit(100)->get(['id', 'name', 'slug'])
                ->map(fn (Brand $brand) => $this->option($brand->id, $brand->name, $brand->slug, '/brand/'.$brand->slug))->values()->all(),
            'posts' => Post::query()->orderBy('title')->limit(100)->get(['id', 'title', 'slug'])
                ->map(fn (Post $post) => $this->option($post->id, $post->title, $post->slug, '/blog/'.$post->slug))->values()->all(),
        ];
    }

    private function option(int $id, string $label, string $slug, string $url): array
    {
        return [
            'id' => $id,
            'label' => $label,
            'slug' => $slug,
            'url' => $url,
        ];
    }
}
