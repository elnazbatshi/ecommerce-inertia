<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductArchiveService
{
    public function archive(Builder $baseQuery, Request $request): array
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'brand' => $request->query('brand'),
            'category' => $request->query('category'),
            'in_stock' => $request->boolean('in_stock', false),
            'discounted' => $request->boolean('discounted', false),
            'vehicle' => $request->query('vehicle'),
            'sort' => (string) $request->query('sort', 'newest'),
            'mode' => (string) $request->query('mode', 'grid'),
            'rows' => (int) $request->query('rows', 12),
        ];

        $rows = max(6, min($filters['rows'], 36));
        $customerId = $request->session()->get('customer_id');

        $query = $baseQuery
            ->with(['brand:id,name,slug', 'category:id,name,slug'])
            ->when($customerId, fn (Builder $builder) => $builder->withExists([
                'wishlistedByCustomers as is_wishlisted' => fn (Builder $wishlist) => $wishlist->where('customers.id', $customerId),
            ]))
            ->where('status', 'active')
            ->when($filters['q'] !== '', function (Builder $builder) use ($filters) {
                $q = $filters['q'];
                $builder->where(function (Builder $qBuilder) use ($q) {
                    $qBuilder->where('name', 'like', "%{$q}%")
                        ->orWhere('sku', 'like', "%{$q}%")
                        ->orWhereHas('brand', fn (Builder $b) => $b->where('name', 'like', "%{$q}%"));
                });
            })
            ->when($filters['brand'], fn (Builder $builder, $brandSlug) => $builder->whereHas('brand', fn (Builder $b) => $b->where('slug', $brandSlug)))
            ->when($filters['category'], fn (Builder $builder, $categorySlug) => $builder->whereHas('category', fn (Builder $c) => $c->where('slug', $categorySlug)))
            ->when($filters['vehicle'], fn (Builder $builder, $vehicleId) => $builder->whereHas('vehicles', fn (Builder $v) => $v->where('vehicles.id', $vehicleId)->orWhere('vehicles.slug', $vehicleId)))
            ->when($filters['in_stock'], fn (Builder $builder) => $builder->where('stock', '>', 0))
            ->when($filters['discounted'], fn (Builder $builder) => $builder->whereNotNull('discount_price'));

        $this->applySort($query, $filters['sort']);

        $products = $query
            ->paginate($rows)
            ->withQueryString()
            ->through(fn (Product $product) => [
                'id' => $product->id,
                'slug' => $product->slug,
                'name' => $product->name,
                'brand' => $product->brand?->name ?? '-',
                'feature' => $product->material ?: ($product->origin ?: 'کیفیت تضمین‌شده فروشگاه'),
                'price' => (float) ($product->discount_price ?: $product->price),
                'oldPrice' => $product->discount_price ? (float) $product->price : null,
                'stock' => (int) ($product->stock ?? 0),
                'inStock' => (int) ($product->stock ?? 0) > 0,
                'isNew' => $product->created_at?->gt(now()->subDays(10)) ?? false,
                'image' => $product->main_image ? Storage::url($product->main_image) : 'https://picsum.photos/seed/product-' . $product->id . '/600/420',
                'is_wishlisted' => (bool) ($product->is_wishlisted ?? false),
            ]);

        $brandOptions = Brand::query()
            ->whereHas('products', fn (Builder $builder) => $builder->where('status', 'active'))
            ->orderBy('name')
            ->get(['name', 'slug'])
            ->map(fn (Brand $brand) => ['label' => $brand->name, 'value' => $brand->slug])
            ->values();

        $categoryOptions = Category::query()
            ->whereHas('products', fn (Builder $builder) => $builder->where('status', 'active'))
            ->orderBy('name')
            ->get(['name', 'slug'])
            ->map(fn (Category $category) => ['label' => $category->name, 'value' => $category->slug])
            ->values();

        return [
            'products' => $products,
            'brands' => $brandOptions,
            'categories' => $categoryOptions,
            'filters' => $filters,
        ];
    }

    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'price_asc' => $query->orderByRaw('COALESCE(discount_price, price) asc'),
            'price_desc' => $query->orderByRaw('COALESCE(discount_price, price) desc'),
            'discount_desc' => $query->orderByRaw('(price - COALESCE(discount_price, price)) desc'),
            'bestselling' => $query->orderByDesc('id'),
            default => $query->latest('id'),
        };
    }
}
