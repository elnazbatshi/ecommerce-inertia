<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleFinderController extends Controller
{
    public function types(): JsonResponse
    {
        return response()->json([
            'data' => VehicleType::query()
                ->active()
                ->ordered()
                ->get(['id', 'name', 'slug'])
                ->map(fn (VehicleType $type) => [
                    'id' => $type->id,
                    'name' => $type->name,
                    'slug' => $type->slug,
                ]),
        ]);
    }

    public function brands(Request $request): JsonResponse
    {
        $request->validate([
            'vehicle_type_id' => ['required', 'integer', 'exists:vehicle_types,id'],
        ]);

        return response()->json([
            'data' => VehicleBrand::query()
                ->active()
                ->ordered()
                ->where('vehicle_type_id', $request->integer('vehicle_type_id'))
                ->get(['id', 'vehicle_type_id', 'name', 'slug'])
                ->map(fn (VehicleBrand $brand) => [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'slug' => $brand->slug,
                ]),
        ]);
    }

    public function vehicles(Request $request): JsonResponse
    {
        $request->validate([
            'vehicle_brand_id' => ['required', 'integer', 'exists:vehicle_brands,id'],
        ]);

        return response()->json([
            'data' => Vehicle::query()
                ->active()
                ->ordered()
                ->where('vehicle_brand_id', $request->integer('vehicle_brand_id'))
                ->get(['id', 'vehicle_brand_id', 'name', 'slug', 'year_from', 'year_to', 'engine'])
                ->map(fn (Vehicle $vehicle) => [
                    'id' => $vehicle->id,
                    'name' => $vehicle->name,
                    'slug' => $vehicle->slug,
                    'year_from' => $vehicle->year_from,
                    'year_to' => $vehicle->year_to,
                    'engine' => $vehicle->engine,
                ]),
        ]);
    }
}
