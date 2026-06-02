<?php

namespace App\Services;

use App\Http\Services\SlugService;
use App\Models\ShippingMethod;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ShippingMethodService
{
    public function __construct(private readonly SlugService $slugService) {}

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return ShippingMethod::query()
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($builder) use ($search) {
                    $builder->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when(isset($filters['is_active']) && $filters['is_active'] !== '', fn ($query) => $query->where('is_active', (bool) $filters['is_active']))
            ->ordered()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): ShippingMethod
    {
        return ShippingMethod::query()->create($this->preparePayload($data));
    }

    public function update(ShippingMethod $shippingMethod, array $data): ShippingMethod
    {
        $shippingMethod->update($this->preparePayload($data, $shippingMethod->id));

        return $shippingMethod->fresh();
    }

    public function delete(ShippingMethod $shippingMethod): void
    {
        $shippingMethod->delete();
    }

    public function toggleStatus(ShippingMethod $shippingMethod): ShippingMethod
    {
        $shippingMethod->update(['is_active' => ! $shippingMethod->is_active]);

        return $shippingMethod->fresh();
    }

    public function options(array $filters = []): Collection
    {
        $limit = max(1, min((int) ($filters['limit'] ?? 50), 100));
        $query = trim((string) ($filters['q'] ?? ''));

        return ShippingMethod::query()
            ->select(['id', 'name', 'type', 'base_cost', 'sort_order'])
            ->active()
            ->when($filters['type'] ?? null, fn ($builder, $type) => $builder->where('type', $type))
            ->when($query !== '', fn ($builder) => $builder->where('name', 'like', "%{$query}%"))
            ->ordered()
            ->limit($limit)
            ->get()
            ->map(fn (ShippingMethod $method) => [
                'id' => $method->id,
                'label' => $method->name,
                'type' => $method->type,
                'base_cost' => (float) $method->base_cost,
            ]);
    }

    private function preparePayload(array $data, ?int $ignoreId = null): array
    {
        $slugInput = trim((string) ($data['slug'] ?? ''));
        $slugBase = $slugInput !== '' ? $slugInput : (string) $data['name'];

        $data['slug'] = $this->slugService->unique(ShippingMethod::class, $slugBase, $ignoreId);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = (bool) ($data['is_active'] ?? true);
        $data['settings'] = is_array($data['settings'] ?? null) ? $data['settings'] : [];

        return $data;
    }
}

