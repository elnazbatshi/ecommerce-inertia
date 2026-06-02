<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProvinceRequest;
use App\Http\Requests\UpdateProvinceRequest;
use App\Http\Resources\ProvinceResource;
use App\Models\Province;
use App\Services\ProvinceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProvinceController extends Controller
{
    public function __construct(private readonly ProvinceService $provinceService) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'is_active', 'rows']);
        $rows = (int) ($filters['rows'] ?? 15);

        return Inertia::render('Provinces/Index', [
            'provinces' => $this->provinceService->paginate($filters, $rows)
                ->through(fn (Province $province) => ProvinceResource::make($province)->resolve()),
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Provinces/Create');
    }

    public function store(StoreProvinceRequest $request): RedirectResponse
    {
        $this->provinceService->create($request->validated());

        return redirect()->route('admin.provinces.index')->with('success', 'استان با موفقیت ایجاد شد.');
    }

    public function edit(Province $province): Response
    {
        return Inertia::render('Provinces/Edit', [
            'province' => ProvinceResource::make($province),
        ]);
    }

    public function update(UpdateProvinceRequest $request, Province $province): RedirectResponse
    {
        $this->provinceService->update($province, $request->validated());

        return redirect()->route('admin.provinces.index')->with('success', 'استان با موفقیت ویرایش شد.');
    }

    public function destroy(Province $province): RedirectResponse
    {
        $this->provinceService->delete($province);

        return back()->with('success', 'استان حذف شد.');
    }

    public function toggleStatus(Province $province): RedirectResponse
    {
        $this->provinceService->toggleStatus($province);

        return back()->with('success', 'وضعیت استان به‌روزرسانی شد.');
    }

    public function options(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        return response()->json($this->provinceService->options($request->only(['q', 'limit'])));
    }
}

