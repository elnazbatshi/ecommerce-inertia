<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMenuRequest;
use App\Http\Requests\UpdateMenuRequest;
use App\Http\Services\MenuService;
use App\Http\Services\SlugService;
use App\Models\Menu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class MenuController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Menus/Index', [
            'menus' => Menu::orderBy('name')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Menus/Create', [
            'locationOptions' => Menu::locationOptions(),
        ]);
    }

    public function store(StoreMenuRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ? app(SlugService::class)->unique(Menu::class, $data['slug']) : app(SlugService::class)->unique(Menu::class, $data['name']);
        $data['is_active'] = $request->boolean('is_active');

        Menu::create($data);

        return Redirect::route('menus.index')->with('success', 'منو با موفقیت ایجاد شد.');
    }

    public function edit(Menu $menu): Response
    {
        $menu->load(['rootItems' => fn ($query) => $query->with('childrenRecursive')]);

        return Inertia::render('Menus/Edit', [
            'menu' => [
                'id' => $menu->id,
                'name' => $menu->name,
                'slug' => $menu->slug,
                'location' => $menu->location,
                'description' => $menu->description,
                'is_active' => $menu->is_active,
                'items' => $this->mapItems($menu->rootItems),
            ],
            'locationOptions' => Menu::locationOptions(),
        ]);
    }

    public function update(UpdateMenuRequest $request, Menu $menu): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $data['slug'] ? app(SlugService::class)->unique(Menu::class, $data['slug'], $menu->id) : app(SlugService::class)->unique(Menu::class, $data['name'], $menu->id);
        $data['is_active'] = $request->boolean('is_active');

        $menu->update($data);

        return Redirect::route('menus.edit', $menu)->with('success', 'منو با موفقیت به‌روز شد.');
    }

    public function destroy(Menu $menu): RedirectResponse
    {
        $menu->delete();

        return Redirect::route('menus.index')->with('success', 'منو حذف شد.');
    }

    public function show(Menu $menu): Response
    {
        $menu->load(['rootItems' => fn ($query) => $query->with('childrenRecursive')]);

        return Inertia::render('Menus/Show', [
            'menu' => [
                'id' => $menu->id,
                'name' => $menu->name,
                'slug' => $menu->slug,
                'location' => $menu->location,
                'description' => $menu->description,
                'is_active' => $menu->is_active,
                'items' => $this->mapItems($menu->rootItems),
            ],
        ]);
    }

    public function location(string $location, MenuService $menuService): array
    {
        return $menuService->getMenuByLocation($location);
    }

    private function mapItems($items): array
    {
        return $items->map(function ($item) {
            return [
                'id' => $item->id,
                'parent_id' => $item->parent_id,
                'title' => $item->title,
                'type' => $item->type,
                'url' => $item->url,
                'route_name' => $item->route_name,
                'route_params' => $item->route_params,
                'target' => $item->target,
                'icon' => $item->icon,
                'css_class' => $item->css_class,
                'rel' => $item->rel,
                'sort_order' => $item->sort_order,
                'is_active' => $item->is_active,
                'children' => $this->mapItems($item->childrenRecursive),
            ];
        })->values()->all();
    }
}
