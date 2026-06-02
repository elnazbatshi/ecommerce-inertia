<?php

namespace App\Services;

use App\Http\Services\SlugService;
use App\Models\PaymentMethod;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PaymentMethodService
{
    public function __construct(private readonly SlugService $slugService) {}

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return PaymentMethod::query()
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($builder) use ($search) {
                    $builder->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($filters['driver'] ?? null, fn ($query, $driver) => $query->where('driver', $driver))
            ->when(isset($filters['is_active']) && $filters['is_active'] !== '', fn ($query) => $query->where('is_active', (bool) $filters['is_active']))
            ->ordered()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data): PaymentMethod
    {
        return PaymentMethod::query()->create($this->preparePayload($data));
    }

    public function update(PaymentMethod $paymentMethod, array $data): PaymentMethod
    {
        $paymentMethod->update($this->preparePayload($data, $paymentMethod->id));

        return $paymentMethod->fresh();
    }

    public function delete(PaymentMethod $paymentMethod): void
    {
        $paymentMethod->delete();
    }

    public function toggleStatus(PaymentMethod $paymentMethod): PaymentMethod
    {
        $paymentMethod->update(['is_active' => ! $paymentMethod->is_active]);

        return $paymentMethod->fresh();
    }

    public function options(array $filters = []): Collection
    {
        $limit = max(1, min((int) ($filters['limit'] ?? 50), 100));
        $query = trim((string) ($filters['q'] ?? ''));

        return PaymentMethod::query()
            ->select(['id', 'name', 'driver', 'sort_order'])
            ->active()
            ->when($filters['driver'] ?? null, fn ($builder, $driver) => $builder->where('driver', $driver))
            ->when($query !== '', fn ($builder) => $builder->where('name', 'like', "%{$query}%"))
            ->ordered()
            ->limit($limit)
            ->get()
            ->map(fn (PaymentMethod $method) => [
                'id' => $method->id,
                'label' => $method->name,
                'driver' => $method->driver,
            ]);
    }

    private function preparePayload(array $data, ?int $ignoreId = null): array
    {
        $slugInput = trim((string) ($data['slug'] ?? ''));
        $slugBase = $slugInput !== '' ? $slugInput : (string) $data['name'];

        $data['slug'] = $this->slugService->unique(PaymentMethod::class, $slugBase, $ignoreId);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_active'] = (bool) ($data['is_active'] ?? true);
        $data['settings'] = is_array($data['settings'] ?? null) ? $data['settings'] : [];

        return $data;
    }
}

