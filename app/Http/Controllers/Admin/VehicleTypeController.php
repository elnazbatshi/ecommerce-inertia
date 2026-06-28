<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVehicleTypeRequest;
use App\Http\Requests\UpdateVehicleTypeRequest;
use App\Http\Resources\VehicleTypeResource;
use App\Models\VehicleType;
use App\Services\VehicleTypeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VehicleTypeController extends Controller
{
    public function __construct(private readonly VehicleTypeService $vehicleTypes) {}

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'is_active', 'rows']);

        return Inertia::render('VehicleTypes/Index', [
            'vehicleTypes' => $this->vehicleTypes
                ->paginate($filters, (int) ($filters['rows'] ?? 15))
                ->through(fn (VehicleType $type) => VehicleTypeResource::make($type)->resolve()),
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('VehicleTypes/Create');
    }

    public function store(StoreVehicleTypeRequest $request): RedirectResponse
    {
        $this->vehicleTypes->store($request->validated());

        return redirect()->route('admin.vehicle-types.index')->with('success', 'نوع وسیله نقلیه ایجاد شد.');
    }

    public function edit(VehicleType $vehicleType): Response
    {
        $vehicleType->loadCount('brands');

        return Inertia::render('VehicleTypes/Edit', [
            'vehicleType' => VehicleTypeResource::make($vehicleType),
        ]);
    }

    public function update(UpdateVehicleTypeRequest $request, VehicleType $vehicleType): RedirectResponse
    {
        $this->vehicleTypes->update($vehicleType, $request->validated());

        return redirect()->route('admin.vehicle-types.index')->with('success', 'نوع وسیله نقلیه ویرایش شد.');
    }

    public function destroy(VehicleType $vehicleType): RedirectResponse
    {
        $this->vehicleTypes->destroy($vehicleType);

        return back()->with('success', 'نوع وسیله نقلیه حذف شد.');
    }

    public function toggleStatus(VehicleType $vehicleType): RedirectResponse
    {
        $this->vehicleTypes->toggleStatus($vehicleType);

        return back()->with('success', 'وضعیت نوع وسیله نقلیه تغییر کرد.');
    }
}
