<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSearchSuggestionRequest;
use App\Http\Requests\UpdateSearchSuggestionRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\SearchSuggestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SearchSuggestionController extends Controller
{
    public function index(Request $request): Response
    {
        $rows = (int) $request->integer('rows', 20);

        $suggestions = SearchSuggestion::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->toString();

                $query->where(function ($builder) use ($search) {
                    $builder->where('title', 'like', "%{$search}%")
                        ->orWhere('keyword', 'like', "%{$search}%")
                        ->orWhere('url', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('type'), fn ($query) => $query->where('type', $request->string('type')))
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->string('status') === 'active'))
            ->ordered()
            ->paginate($rows)
            ->withQueryString()
            ->through(fn (SearchSuggestion $suggestion) => $this->serializeSuggestion($suggestion));

        return Inertia::render('Search/Suggestions', [
            'suggestions' => $suggestions,
            'products' => $this->productOptions(),
            'categories' => $this->categoryOptions(),
            'brands' => $this->brandOptions(),
            'types' => $this->typeOptions(),
            'statuses' => $this->statusOptions(),
            'filters' => $request->only(['search', 'type', 'status', 'rows']),
        ]);
    }

    public function store(StoreSearchSuggestionRequest $request): RedirectResponse
    {
        SearchSuggestion::query()->create($this->normalizePayload($request->validated()));

        return back()->with('success', 'پیشنهاد جستجو ساخته شد.');
    }

    public function update(UpdateSearchSuggestionRequest $request, SearchSuggestion $suggestion): RedirectResponse
    {
        $suggestion->update($this->normalizePayload($request->validated()));

        return back()->with('success', 'پیشنهاد جستجو به‌روزرسانی شد.');
    }

    public function destroy(SearchSuggestion $suggestion): RedirectResponse
    {
        $suggestion->delete();

        return back()->with('success', 'پیشنهاد جستجو حذف شد.');
    }

    public function reorder(Request $request): JsonResponse
    {
        $rows = $request->validate([
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer', 'exists:search_suggestions,id'],
            'items.*.sort_order' => ['required', 'integer'],
        ]);

        DB::transaction(function () use ($rows): void {
            foreach ($rows['items'] as $item) {
                SearchSuggestion::query()->whereKey($item['id'])->update(['sort_order' => $item['sort_order']]);
            }
        });

        return response()->json(['message' => 'done']);
    }

    public function toggleStatus(SearchSuggestion $suggestion): RedirectResponse
    {
        $suggestion->update(['is_active' => !$suggestion->is_active]);

        return back()->with('success', 'وضعیت پیشنهاد به‌روزرسانی شد.');
    }

    public function autocompleteProducts(Request $request): JsonResponse
    {
        return response()->json($this->productOptions($request->string('query')->toString()));
    }

    public function autocompleteCategories(Request $request): JsonResponse
    {
        return response()->json($this->categoryOptions($request->string('query')->toString()));
    }

    public function autocompleteBrands(Request $request): JsonResponse
    {
        return response()->json($this->brandOptions($request->string('query')->toString()));
    }

    private function normalizePayload(array $data): array
    {
        if (($data['type'] ?? null) !== 'custom') {
            $reference = $this->findReference($data['type'], $data['reference_id'] ?? null);
            $data['url'] = $reference ? $this->referenceUrl($data['type'], $reference->slug) : null;
        }

        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active'] = (bool) ($data['is_active'] ?? false);

        return $data;
    }

    private function serializeSuggestion(SearchSuggestion $suggestion): array
    {
        $reference = $this->findReference($suggestion->type, $suggestion->reference_id);

        return [
            'id' => $suggestion->id,
            'title' => $suggestion->title,
            'type' => $suggestion->type,
            'reference_id' => $suggestion->reference_id,
            'reference' => $reference ? $this->referenceOption($suggestion->type, $reference) : null,
            'reference_title' => $reference?->name,
            'url' => $suggestion->resolveUrl(),
            'keyword' => $suggestion->keyword,
            'icon' => $suggestion->icon,
            'sort_order' => $suggestion->sort_order,
            'is_active' => $suggestion->is_active,
            'starts_at' => optional($suggestion->starts_at)->format('Y-m-d H:i:s'),
            'ends_at' => optional($suggestion->ends_at)->format('Y-m-d H:i:s'),
        ];
    }

    private function productOptions(?string $query = null): array
    {
        return Product::query()
            ->when($query, function ($builder) use ($query) {
                $builder->where(function ($inner) use ($query) {
                    $inner->where('name', 'like', "%{$query}%")
                        ->orWhere('sku', 'like', "%{$query}%");
                });
            })
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'slug', 'sku', 'main_image'])
            ->map(fn (Product $product) => $this->referenceOption('product', $product))
            ->values()
            ->all();
    }

    private function categoryOptions(?string $query = null): array
    {
        return Category::query()
            ->when($query, fn ($builder) => $builder->where('name', 'like', "%{$query}%"))
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'slug', 'icon'])
            ->map(fn (Category $category) => $this->referenceOption('category', $category))
            ->values()
            ->all();
    }

    private function brandOptions(?string $query = null): array
    {
        return Brand::query()
            ->when($query, fn ($builder) => $builder->where('name', 'like', "%{$query}%"))
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'slug', 'logo'])
            ->map(fn (Brand $brand) => $this->referenceOption('brand', $brand))
            ->values()
            ->all();
    }

    private function referenceOption(string $type, Product|Category|Brand $model): array
    {
        $image = match ($type) {
            'product' => $model->main_image ? Storage::url($model->main_image) : null,
            'brand' => $model->logo ? Storage::url($model->logo) : null,
            default => null,
        };

        return [
            'id' => $model->id,
            'name' => $model->name,
            'slug' => $model->slug,
            'url' => $this->referenceUrl($type, $model->slug),
            'image_url' => $image,
            'icon' => $type === 'category' ? ($model->icon ?? null) : null,
            'meta' => $type === 'product' ? ($model->sku ?? null) : null,
        ];
    }

    private function findReference(?string $type, mixed $id): Product|Category|Brand|null
    {
        if (!$id) {
            return null;
        }

        return match ($type) {
            'product' => Product::query()->find($id),
            'category' => Category::query()->find($id),
            'brand' => Brand::query()->find($id),
            default => null,
        };
    }

    private function referenceUrl(string $type, string $slug): string
    {
        return match ($type) {
            'product' => "/products/{$slug}",
            'category' => "/category/{$slug}",
            'brand' => "/brand/{$slug}",
            default => '',
        };
    }

    private function typeOptions(): array
    {
        return [
            ['label' => 'محصول', 'value' => 'product'],
            ['label' => 'دسته‌بندی', 'value' => 'category'],
            ['label' => 'برند', 'value' => 'brand'],
            ['label' => 'سفارشی', 'value' => 'custom'],
        ];
    }

    private function statusOptions(): array
    {
        return [
            ['label' => 'فعال', 'value' => 'active'],
            ['label' => 'غیرفعال', 'value' => 'inactive'],
        ];
    }
}
