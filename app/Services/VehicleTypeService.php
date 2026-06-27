<?php

namespace App\Services;

use App\Http\Services\SlugService;
use App\Models\VehicleType;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class VehicleTypeService
{
    public function __construct(private readonly SlugService $slugService) {}

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return VehicleType::query()
            ->withCount('brands')
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            })
            ->when(isset($filters['is_active']) && $filters['is_active'] !== '', fn ($query) => $query->where('is_active', (bool) $filters['is_active']))
            ->ordered()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function options(): Collection
    {
        return VehicleType::query()
            ->ordered()
            ->get(['id', 'name', 'slug'])
            ->map(fn (VehicleType $type) => ['label' => $type->name, 'value' => $type->id, 'slug' => $type->slug]);
    }

    public function store(array $data): VehicleType
    {
        return VehicleType::query()->create($this->payload($data));
    }

    public function update(VehicleType $vehicleType, array $data): VehicleType
    {
        $vehicleType->update($this->payload($data, $vehicleType->id));

        return $vehicleType->refresh();
    }

    public function destroy(VehicleType $vehicleType): void
    {
        $vehicleType->delete();
    }

    public function toggleStatus(VehicleType $vehicleType): VehicleType
    {
        $vehicleType->update(['is_active' => ! $vehicleType->is_active]);

        return $vehicleType->refresh();
    }

    private function payload(array $data, ?int $ignoreId = null): array
    {
        $slug = trim((string) ($data['slug'] ?? ''));
        $data['slug'] = $this->slugService->unique(VehicleType::class, $slug !== '' ? $slug : $data['name'], $ignoreId);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = (bool) ($data['is_active'] ?? true);

        return $data;
    }
}
