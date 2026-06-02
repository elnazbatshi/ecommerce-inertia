<?php

namespace App\Services;

use App\Http\Services\SlugService;
use App\Models\Province;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ProvinceService
{
    public function __construct(private readonly SlugService $slugService) {}

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return Province::query()
            ->withCount('cities')
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($builder) use ($search) {
                    $builder->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%");
                });
            })
            ->when(isset($filters['is_active']) && $filters['is_active'] !== '', fn ($query) => $query->where('is_active', (bool) $filters['is_active']))
            ->ordered()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): Province
    {
        return Province::query()->create($this->preparePayload($data));
    }

    public function update(Province $province, array $data): Province
    {
        $province->update($this->preparePayload($data, $province->id));

        return $province->fresh();
    }

    public function delete(Province $province): void
    {
        $province->delete();
    }

    public function toggleStatus(Province $province): Province
    {
        $province->update(['is_active' => ! $province->is_active]);

        return $province->fresh();
    }

    public function options(array $filters = []): Collection
    {
        $limit = max(1, min((int) ($filters['limit'] ?? 100), 200));
        $q = trim((string) ($filters['q'] ?? ''));

        return Province::query()
            ->select(['id', 'name', 'slug'])
            ->active()
            ->when($q !== '', fn ($builder) => $builder->where('name', 'like', "%{$q}%"))
            ->ordered()
            ->limit($limit)
            ->get()
            ->map(fn (Province $province) => [
                'id' => $province->id,
                'label' => $province->name,
                'slug' => $province->slug,
            ]);
    }

    private function preparePayload(array $data, ?int $ignoreId = null): array
    {
        $slugBase = trim((string) ($data['slug'] ?? '')) !== '' ? $data['slug'] : $data['name'];
        $data['slug'] = $this->slugService->unique(Province::class, $slugBase, $ignoreId);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = (bool) ($data['is_active'] ?? true);

        return $data;
    }
}

