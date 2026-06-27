<?php

namespace App\Services;

use App\Http\Services\SlugService;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class VehicleService
{
    public function __construct(private readonly SlugService $slugService) {}

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Vehicle::query()
            ->with(['brand.vehicleType:id,name,slug', 'imageMedia:id,path,original_name'])
            ->withCount('products')
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($builder) use ($search) {
                    $builder->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('engine', 'like', "%{$search}%")
                        ->orWhere('trim', 'like', "%{$search}%")
                        ->orWhereHas('brand', fn ($brandQuery) => $brandQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($filters['vehicle_type_id'] ?? null, fn ($query, $typeId) => $query->whereHas('brand', fn ($brand) => $brand->where('vehicle_type_id', $typeId)))
            ->when($filters['vehicle_brand_id'] ?? null, fn ($query, $brandId) => $query->where('vehicle_brand_id', $brandId))
            ->when(isset($filters['is_active']) && $filters['is_active'] !== '', fn ($query) => $query->where('is_active', (bool) $filters['is_active']))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function store(array $data): Vehicle
    {
        $payload = $this->preparePayload($data);

        return Vehicle::query()->create($payload);
    }

    public function update(Vehicle $vehicle, array $data): Vehicle
    {
        $payload = $this->preparePayload($data, $vehicle->id);
        $vehicle->update($payload);

        return $vehicle->fresh(['brand.vehicleType:id,name,slug', 'imageMedia:id,path,original_name']);
    }

    public function destroy(Vehicle $vehicle): void
    {
        $vehicle->delete();
    }

    public function toggleStatus(Vehicle $vehicle): Vehicle
    {
        $vehicle->update(['is_active' => ! $vehicle->is_active]);

        return $vehicle->fresh(['brand.vehicleType:id,name,slug', 'imageMedia:id,path,original_name']);
    }

    public function vehicleBrandOptions(): Collection
    {
        return VehicleBrand::query()
            ->select(['id', 'vehicle_type_id', 'name', 'slug', 'type'])
            ->with('vehicleType:id,name,slug')
            ->orderBy('vehicle_type_id')
            ->orderBy('name')
            ->get();
    }

    public function options(?string $query = null, ?string $type = null, int $limit = 20): Collection
    {
        $limit = max(1, min($limit, 100));

        return Vehicle::query()
            ->with(['brand.vehicleType:id,name,slug'])
            ->where('is_active', true)
            ->when($type, fn ($builder) => $builder->whereHas('brand.vehicleType', fn ($typeQuery) => $typeQuery->where('slug', $type)->orWhere('id', $type)))
            ->when($query, function ($builder, $query) {
                $builder->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('slug', 'like', "%{$query}%")
                        ->orWhereHas('brand', fn ($brandQuery) => $brandQuery->where('name', 'like', "%{$query}%"));
                });
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit($limit)
            ->get()
            ->map(fn (Vehicle $vehicle) => [
                'id' => $vehicle->id,
                'label' => trim(implode(' ', array_filter([$vehicle->brand?->name, $vehicle->name, $vehicle->trim]))),
                'brand' => $vehicle->brand?->name,
                'type' => $vehicle->brand?->vehicleType?->slug,
                'vehicle_type' => $vehicle->brand?->vehicleType?->name,
            ]);
    }

    private function preparePayload(array $data, ?int $ignoreId = null): array
    {
        $brand = VehicleBrand::query()->with('vehicleType:id,name,slug')->find($data['vehicle_brand_id']);
        $data['type'] = match ($brand?->vehicleType?->slug) {
            'motorcycle' => 'motorcycle',
            default => 'car',
        };
        $slugInput = trim((string) ($data['slug'] ?? ''));
        $baseTitle = trim(implode(' ', array_filter([$brand?->name, $data['name'] ?? null, $data['trim'] ?? null])));
        $slugBase = $slugInput !== '' ? $slugInput : ($baseTitle !== '' ? $baseTitle : (string) ($data['name'] ?? 'vehicle'));

        $data['slug'] = $this->slugService->unique(Vehicle::class, $slugBase, $ignoreId);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = (bool) ($data['is_active'] ?? true);

        return $data;
    }
}
