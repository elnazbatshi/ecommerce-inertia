<?php

namespace App\Services;

use App\Http\Services\SlugService;
use App\Models\VehicleBrand;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class VehicleBrandService
{
    public function __construct(private readonly SlugService $slugService) {}

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return VehicleBrand::query()
            ->with('logoMedia:id,path,original_name')
            ->withCount('vehicles')
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($builder) use ($search) {
                    $builder->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('country', 'like', "%{$search}%");
                });
            })
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when(isset($filters['is_active']) && $filters['is_active'] !== '', fn ($query) => $query->where('is_active', (bool) $filters['is_active']))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function store(array $data): VehicleBrand
    {
        $payload = $this->preparePayload($data);

        return VehicleBrand::query()->create($payload);
    }

    public function update(VehicleBrand $vehicleBrand, array $data): VehicleBrand
    {
        $payload = $this->preparePayload($data, $vehicleBrand->id);
        $vehicleBrand->update($payload);

        return $vehicleBrand->fresh(['logoMedia:id,path,original_name']);
    }

    public function destroy(VehicleBrand $vehicleBrand): void
    {
        $vehicleBrand->delete();
    }

    public function toggleStatus(VehicleBrand $vehicleBrand): VehicleBrand
    {
        $vehicleBrand->update(['is_active' => ! $vehicleBrand->is_active]);

        return $vehicleBrand->fresh(['logoMedia:id,path,original_name']);
    }

    private function preparePayload(array $data, ?int $ignoreId = null): array
    {
        $slugInput = trim((string) ($data['slug'] ?? ''));
        $slugBase = $slugInput !== '' ? $slugInput : (string) $data['name'];
        $data['slug'] = $this->slugService->unique(VehicleBrand::class, $slugBase, $ignoreId);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = (bool) ($data['is_active'] ?? true);

        return $data;
    }
}

