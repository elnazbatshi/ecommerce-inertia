<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReorderMenuItemsRequest;
use App\Http\Requests\StoreMenuItemRequest;
use App\Http\Requests\UpdateMenuItemRequest;
use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;

class MenuItemController extends Controller
{
    public function store(StoreMenuItemRequest $request, Menu $menu): RedirectResponse
    {
        $data = $this->preparePayload($request->validated());
        $data['menu_id'] = $menu->id;
        $data['sort_order'] = $menu->items()->max('sort_order') + 1;

        MenuItem::create($data);

        return Redirect::route('menus.edit', $menu)->with('success', 'آیتم منو اضافه شد.');
    }

    public function update(UpdateMenuItemRequest $request, Menu $menu, MenuItem $item): RedirectResponse
    {
        $this->ensureMenuItemBelongsToMenu($menu, $item);

        $item->update($this->preparePayload($request->validated()));

        return Redirect::route('menus.edit', $menu)->with('success', 'آیتم منو به‌روز شد.');
    }

    public function destroy(Menu $menu, MenuItem $item): RedirectResponse
    {
        $this->ensureMenuItemBelongsToMenu($menu, $item);

        $item->delete();

        return Redirect::route('menus.edit', $menu)->with('success', 'آیتم منو حذف شد.');
    }

    public function toggleStatus(Menu $menu, MenuItem $item): RedirectResponse
    {
        $this->ensureMenuItemBelongsToMenu($menu, $item);

        $item->update(['is_active' => ! $item->is_active]);

        return Redirect::route('menus.edit', $menu)->with('success', 'وضعیت آیتم منو تغییر کرد.');
    }

    public function reorder(ReorderMenuItemsRequest $request, Menu $menu): RedirectResponse
    {
        $this->syncOrder($request->all(), $menu->id);

        return Redirect::route('menus.edit', $menu)->with('success', 'ترتیب آیتم‌های منو ذخیره شد.');
    }

    private function preparePayload(array $data): array
    {
        if (isset($data['route_params']) && is_string($data['route_params'])) {
            $data['route_params'] = $data['route_params'] !== '' ? json_decode($data['route_params'], true) : null;
        }

        if ($data['type'] === 'external') {
            $data['route_name'] = null;
        }

        if ($data['type'] === 'internal') {
            $data['url'] = $data['url'] ? ltrim($data['url'], '/') : null;
        }

        return array_merge($data, [
            'is_active' => $data['is_active'] ?? false,
            'target' => $data['target'] ?? '_self',
            'sort_order' => $data['sort_order'] ?? 0,
        ]);
    }

    private function syncOrder(array $items, int $menuId, ?int $parentId = null): void
    {
        foreach ($items as $item) {
            $menuItem = MenuItem::where('menu_id', $menuId)->findOrFail($item['id']);
            $menuItem->update([
                'parent_id' => $parentId,
                'sort_order' => $item['sort_order'],
            ]);

            if (! empty($item['children']) && is_array($item['children'])) {
                $this->syncOrder($item['children'], $menuId, $menuItem->id);
            }
        }
    }

    private function ensureMenuItemBelongsToMenu(Menu $menu, MenuItem $item): void
    {
        abort_if($item->menu_id !== $menu->id, 404);
    }
}
