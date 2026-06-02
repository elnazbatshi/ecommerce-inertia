<?php

namespace App\Services;

use App\Http\Services\SlugService;
use App\Models\City;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CityService
{
    public function __construct(private readonly SlugService $slugService) {}

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return City::query()
            ->with('province:id,name,slug')
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($builder) use ($search) {
                    $builder->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhereHas('province', fn ($provinceQuery) => $provinceQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($filters['province_id'] ?? null, fn ($query, $provinceId) => $query->where('province_id', $provinceId))
            ->when(isset($filters['is_active']) && $filters['is_active'] !== '', fn ($query) => $query->where('is_active', (bool) $filters['is_active']))
            ->ordered()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): City
    {
        return City::query()->create($this->preparePayload($data));
    }

    public function update(City $city, array $data): City
    {
        $city->update($this->preparePayload($data, $city->id));

        return $city->fresh(['province:id,name,slug']);
    }

    public function delete(City $city): void
    {
        $city->delete();
    }

    public function toggleStatus(City $city): City
    {
        $city->update(['is_active' => ! $city->is_active]);

        return $city->fresh(['province:id,name,slug']);
    }

    public function options(array $filters = []): Collection
    {
        $limit = max(1, min((int) ($filters['limit'] ?? 100), 200));
        $q = trim((string) ($filters['q'] ?? ''));

        return City::query()
            ->select(['id', 'province_id', 'name'])
            ->active()
            ->when($filters['province_id'] ?? null, fn ($builder, $provinceId) => $builder->where('province_id', $provinceId))
            ->when($q !== '', fn ($builder) => $builder->where('name', 'like', "%{$q}%"))
            ->ordered()
            ->limit($limit)
            ->get()
            ->map(fn (City $city) => [
                'id' => $city->id,
                'label' => $city->name,
                'province_id' => $city->province_id,
            ]);
    }

    private function preparePayload(array $data, ?int $ignoreId = null): array
    {
        $slugInput = trim((string) ($data['slug'] ?? ''));
        $base = $this->slugService->make($slugInput !== '' ? $slugInput : (string) $data['name']);

        $slug = $base;
        $counter = 2;

        while (City::query()
            ->where('province_id', $data['province_id'])
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        $data['slug'] = $slug;
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = (bool) ($data['is_active'] ?? true);

        return $data;
    }
}

