<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReorderMenuRequest;
use App\Http\Requests\StoreMenuItemRequest;
use App\Http\Requests\UpdateMenuItemRequest;
use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

class MenuItemController extends Controller
{
    public function store(StoreMenuItemRequest $request, Menu $menu): RedirectResponse|JsonResponse
    {
        $data = $this->preparePayload($request->validated());
        $data['menu_id'] = $menu->id;
        $data['sort_order'] = $menu->items()->where('parent_id', $data['parent_id'] ?? null)->max('sort_order') + 1;
        $data['depth'] = $this->depthForParent($data['parent_id'] ?? null);

        $item = MenuItem::create($data);

        if ($request->wantsJson()) {
            return response()->json(['item' => $item], 201);
        }

        return Redirect::route('menus.builder', $menu)->with('success', 'آیتم به منو اضافه شد.');
    }

    public function update(UpdateMenuItemRequest $request, Menu $menu, MenuItem $item): RedirectResponse|JsonResponse
    {
        $this->ensureMenuItemBelongsToMenu($menu, $item);

        $data = $this->preparePayload($request->validated());
        $data['depth'] = $this->depthForParent($data['parent_id'] ?? null);
        $item->update($data);

        if ($request->wantsJson()) {
            return response()->json(['item' => $item->fresh()]);
        }

        return Redirect::route('menus.builder', $menu)->with('success', 'آیتم منو به‌روزرسانی شد.');
    }

    public function destroy(Menu $menu, MenuItem $item): RedirectResponse|JsonResponse
    {
        $this->ensureMenuItemBelongsToMenu($menu, $item);
        $item->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'deleted_id' => $item->id]);
        }

        return Redirect::route('menus.builder', $menu)->with('success', 'آیتم منو حذف شد.');
    }

    public function toggleStatus(Menu $menu, MenuItem $item): RedirectResponse
    {
        $this->ensureMenuItemBelongsToMenu($menu, $item);
        $item->update(['is_active' => ! $item->is_active]);

        return Redirect::route('menus.builder', $menu)->with('success', 'وضعیت آیتم منو تغییر کرد.');
    }

    public function reorder(ReorderMenuRequest $request, Menu $menu): JsonResponse
    {
        DB::transaction(fn () => $this->syncOrder($request->input('items'), $menu->id));

        return response()->json(['success' => true]);
    }

    private function preparePayload(array $data): array
    {
        if (in_array($data['type'], ['custom', 'external'], true)) {
            $data['reference_id'] = null;
            $data['route_name'] = null;
            $data['route_params'] = null;
        }

        if (in_array($data['type'], ['page', 'category', 'product', 'brand', 'post'], true)) {
            $slug = $data['route_params']['slug'] ?? trim((string) ($data['url'] ?? ''), '/');
            $data['url'] = $slug;
            $data['route_params'] = $slug ? ['slug' => $slug] : null;
        }

        return array_merge($data, [
            'is_active' => $data['is_active'] ?? true,
            'target' => $data['target'] ?? '_self',
            'sort_order' => $data['sort_order'] ?? 0,
            'depth' => $data['depth'] ?? 0,
        ]);
    }

    private function syncOrder(array $items, int $menuId, ?int $parentId = null, int $depth = 0): void
    {
        foreach (array_values($items) as $index => $item) {
            $menuItem = MenuItem::query()
                ->where('menu_id', $menuId)
                ->findOrFail($item['id']);

            $menuItem->update([
                'parent_id' => $parentId,
                'sort_order' => $index,
                'depth' => $depth,
            ]);

            $this->syncOrder($item['children'] ?? [], $menuId, $menuItem->id, $depth + 1);
        }
    }

    private function depthForParent(?int $parentId): int
    {
        if (! $parentId) {
            return 0;
        }

        return ((int) MenuItem::query()->whereKey($parentId)->value('depth')) + 1;
    }

    private function ensureMenuItemBelongsToMenu(Menu $menu, MenuItem $item): void
    {
        abort_if($item->menu_id !== $menu->id, 404);
    }
}
