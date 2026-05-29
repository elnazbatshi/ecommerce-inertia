<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVehicleBrandRequest;
use App\Http\Requests\UpdateVehicleBrandRequest;
use App\Http\Resources\VehicleBrandResource;
use App\Models\VehicleBrand;
use App\Services\VehicleBrandService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VehicleBrandController extends Controller
{
    public function __construct(private readonly VehicleBrandService $vehicleBrandService) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'type', 'is_active', 'rows']);
        $rows = (int) ($filters['rows'] ?? 15);
        $brands = $this->vehicleBrandService->paginate($filters, $rows)
            ->through(fn (VehicleBrand $brand) => VehicleBrandResource::make($brand)->resolve());

        return Inertia::render('VehicleBrands/Index', [
            'brands' => $brands,
            'filters' => $filters,
            'typeOptions' => [
                ['label' => 'همه', 'value' => null],
                ['label' => 'موتور سیکلت', 'value' => 'motorcycle'],
                ['label' => 'خودرو', 'value' => 'car'],
                ['label' => 'عمومی', 'value' => 'universal'],
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('VehicleBrands/Create');
    }

    public function store(StoreVehicleBrandRequest $request): RedirectResponse|JsonResponse
    {
        $brand = $this->vehicleBrandService->store($request->validated());
        $brand->load(['logoMedia:id,path,original_name'])->loadCount('vehicles');

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'message' => 'برند خودرو با موفقیت ایجاد شد.',
                'data' => VehicleBrandResource::make($brand),
            ], 201);
        }

        return redirect()->route('admin.vehicle-brands.index')->with('success', 'برند خودرو با موفقیت ایجاد شد.');
    }

    public function edit(VehicleBrand $vehicleBrand): Response
    {
        $vehicleBrand->load(['logoMedia:id,path,original_name'])->loadCount('vehicles');

        return Inertia::render('VehicleBrands/Edit', [
            'brand' => VehicleBrandResource::make($vehicleBrand),
        ]);
    }

    public function update(UpdateVehicleBrandRequest $request, VehicleBrand $vehicleBrand): RedirectResponse
    {
        $this->vehicleBrandService->update($vehicleBrand, $request->validated());

        return redirect()->route('admin.vehicle-brands.index')->with('success', 'برند خودرو با موفقیت ویرایش شد.');
    }

    public function destroy(VehicleBrand $vehicleBrand): RedirectResponse
    {
        $this->vehicleBrandService->destroy($vehicleBrand);

        return back()->with('success', 'برند خودرو حذف شد.');
    }

    public function toggleStatus(VehicleBrand $vehicleBrand): RedirectResponse
    {
        $this->vehicleBrandService->toggleStatus($vehicleBrand);

        return back()->with('success', 'وضعیت برند خودرو به‌روزرسانی شد.');
    }
}
