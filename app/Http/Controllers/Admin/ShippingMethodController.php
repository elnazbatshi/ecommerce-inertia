<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreShippingMethodRequest;
use App\Http\Requests\UpdateShippingMethodRequest;
use App\Http\Resources\ShippingMethodResource;
use App\Models\ShippingMethod;
use App\Services\ShippingMethodService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShippingMethodController extends Controller
{
    public function __construct(private readonly ShippingMethodService $shippingMethodService) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'type', 'is_active', 'rows']);
        $rows = (int) ($filters['rows'] ?? 15);

        return Inertia::render('ShippingMethods/Index', [
            'shippingMethods' => $this->shippingMethodService->paginate($filters, $rows)
                ->through(fn (ShippingMethod $item) => ShippingMethodResource::make($item)->resolve()),
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('ShippingMethods/Create');
    }

    public function store(StoreShippingMethodRequest $request): RedirectResponse
    {
        $this->shippingMethodService->create($request->validated());

        return redirect()->route('admin.shipping-methods.index')->with('success', 'روش ارسال با موفقیت ایجاد شد.');
    }

    public function edit(ShippingMethod $shippingMethod): Response
    {
        return Inertia::render('ShippingMethods/Edit', [
            'shippingMethod' => ShippingMethodResource::make($shippingMethod)->resolve(),
        ]);
    }

    public function update(UpdateShippingMethodRequest $request, ShippingMethod $shippingMethod): RedirectResponse
    {
        $this->shippingMethodService->update($shippingMethod, $request->validated());

        return redirect()->route('admin.shipping-methods.index')->with('success', 'روش ارسال با موفقیت ویرایش شد.');
    }

    public function destroy(ShippingMethod $shippingMethod): RedirectResponse
    {
        $this->shippingMethodService->delete($shippingMethod);

        return back()->with('success', 'روش ارسال حذف شد.');
    }

    public function toggleStatus(ShippingMethod $shippingMethod): RedirectResponse
    {
        $this->shippingMethodService->toggleStatus($shippingMethod);

        return back()->with('success', 'وضعیت روش ارسال به‌روزرسانی شد.');
    }

    public function options(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'in:fixed,free,weight_based,city_based,pickup'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        return response()->json($this->shippingMethodService->options($request->only(['q', 'type', 'limit'])));
    }
}
