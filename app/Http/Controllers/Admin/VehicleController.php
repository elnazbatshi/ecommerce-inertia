<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVehicleRequest;
use App\Http\Requests\UpdateVehicleRequest;
use App\Http\Resources\VehicleResource;
use App\Models\Vehicle;
use App\Services\VehicleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VehicleController extends Controller
{
    public function __construct(private readonly VehicleService $vehicleService) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'type', 'vehicle_brand_id', 'is_active', 'rows']);
        $rows = (int) ($filters['rows'] ?? 15);
        $brands = $this->vehicleService->vehicleBrandOptions();
        $vehicles = $this->vehicleService->paginate($filters, $rows)
            ->through(fn (Vehicle $vehicle) => VehicleResource::make($vehicle)->resolve());

        return Inertia::render('Vehicles/Index', [
            'vehicles' => $vehicles,
            'filters' => $filters,
            'typeOptions' => [
                ['label' => 'همه', 'value' => null],
                ['label' => 'موتور سیکلت', 'value' => 'motorcycle'],
                ['label' => 'خودرو', 'value' => 'car'],
            ],
            'brandOptions' => $brands->map(fn ($brand) => [
                'label' => $brand->name,
                'value' => $brand->id,
                'type' => $brand->type,
            ])->values(),
        ]);
    }

    public function create(): Response
    {
        $brands = $this->vehicleService->vehicleBrandOptions();

        return Inertia::render('Vehicles/Create', [
            'brandOptions' => $brands->map(fn ($brand) => [
                'label' => $brand->name,
                'value' => $brand->id,
                'type' => $brand->type,
            ])->values(),
        ]);
    }

    public function store(StoreVehicleRequest $request): RedirectResponse
    {
        $this->vehicleService->store($request->validated());

        return redirect()->route('admin.vehicles.index')->with('success', 'خودرو با موفقیت ایجاد شد.');
    }

    public function edit(Vehicle $vehicle): Response
    {
        $vehicle->load(['brand:id,name,slug,type', 'imageMedia:id,path,original_name'])->loadCount('products');
        $brands = $this->vehicleService->vehicleBrandOptions();

        return Inertia::render('Vehicles/Edit', [
            'vehicle' => VehicleResource::make($vehicle),
            'brandOptions' => $brands->map(fn ($brand) => [
                'label' => $brand->name,
                'value' => $brand->id,
                'type' => $brand->type,
            ])->values(),
        ]);
    }

    public function update(UpdateVehicleRequest $request, Vehicle $vehicle): RedirectResponse
    {
        $this->vehicleService->update($vehicle, $request->validated());

        return redirect()->route('admin.vehicles.index')->with('success', 'خودرو با موفقیت ویرایش شد.');
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        $this->vehicleService->destroy($vehicle);

        return back()->with('success', 'خودرو حذف شد.');
    }

    public function toggleStatus(Vehicle $vehicle): RedirectResponse
    {
        $this->vehicleService->toggleStatus($vehicle);

        return back()->with('success', 'وضعیت خودرو به‌روزرسانی شد.');
    }

    public function options(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'in:car,motorcycle'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $options = $this->vehicleService->options(
            $request->string('q')->toString(),
            $request->string('type')->toString() ?: null,
            $request->integer('limit', 20),
        );

        return response()->json($options);
    }
}

