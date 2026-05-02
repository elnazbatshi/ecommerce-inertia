<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $sortField = in_array($request->input('sortField'), ['id', 'name', 'sku', 'price', 'status', 'type'], true)
            ? $request->input('sortField')
            : 'id';
        $sortDirection = $request->input('sortOrder') === 'asc' ? 'asc' : 'desc';
        $perPage = max(1, min((int) $request->integer('rows', 10), 100));

        $products = Product::query()
            ->with(['category:id,name', 'brand:id,name'])
            ->when($request->string('search')->toString(), function ($query, string $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('barcode', 'like', "%{$search}%");
                });
            })
            ->when($request->integer('category_id'), fn ($query, $id) => $query->where('category_id', $id))
            ->when($request->integer('brand_id'), fn ($query, $id) => $query->where('brand_id', $id))
            ->when($request->input('status'), fn ($query, $status) => $query->where('status', $status))
            ->when($request->input('type'), fn ($query, $type) => $query->where('type', $type))
            ->orderBy($sortField, $sortDirection)
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Product $product) => $this->formatProduct($product));

        return Inertia::render('Products/Index', [
            'products' => $products,
            'filters' => $request->only(['search', 'category_id', 'brand_id', 'status', 'type', 'sortField', 'sortOrder', 'rows']),
            'categories' => Category::query()->select(['id', 'name', 'slug'])->orderBy('name')->get(),
            'brands' => Brand::query()->select(['id', 'name', 'slug'])->orderBy('name')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Products/Create', $this->formData());
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $product = Product::create($this->productPayload($request));
            $this->storeGalleryImages($request, $product);
            $this->syncVariants($request, $product);
        });

        return redirect()->route('products.index')->with('success', 'محصول ایجاد شد.');
    }

    public function edit(Product $product): Response
    {
        $product->load(['images', 'variants.attributeValues.attribute', 'category:id,name', 'brand:id,name']);

        return Inertia::render('Products/Edit', [
            ...$this->formData(),
            'product' => $this->formatProduct($product, true),
        ]);
    }

    public function show(Product $product): RedirectResponse
    {
        return redirect()->route('products.edit', $product);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        DB::transaction(function () use ($request, $product) {
            $product->update($this->productPayload($request, $product));
            $this->deleteImages($request, $product);
            $this->storeGalleryImages($request, $product);
            $this->deleteVariants($request, $product);
            $this->syncVariants($request, $product);
        });

        return redirect()->route('products.index')->with('success', 'محصول ویرایش شد.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return back()->with('success', 'محصول حذف شد.');
    }

    public function destroyImage(ProductImage $image): RedirectResponse
    {
        Storage::disk('public')->delete($image->image);
        $image->delete();

        return back()->with('success', 'تصویر حذف شد.');
    }

    public function destroyVariant(ProductVariant $variant): RedirectResponse
    {
        if ($variant->image) {
            Storage::disk('public')->delete($variant->image);
        }

        $variant->delete();

        return back()->with('success', 'تنوع حذف شد.');
    }

    private function formData(): array
    {
        return [
            'categories' => Category::query()->select(['id', 'name', 'slug'])->orderBy('name')->get(),
            'brands' => Brand::query()->select(['id', 'name', 'slug'])->orderBy('name')->get(),
            'attributes' => Attribute::query()->with('values:id,attribute_id,value')->orderBy('name')->get(),
            'statusOptions' => [
                ['label' => 'فعال', 'value' => 'active'],
                ['label' => 'غیرفعال', 'value' => 'inactive'],
                ['label' => 'پیش‌نویس', 'value' => 'draft'],
            ],
            'typeOptions' => [
                ['label' => 'ساده', 'value' => 'simple'],
                ['label' => 'متغیر', 'value' => 'variable'],
            ],
        ];
    }

    private function productPayload(Request $request, ?Product $product = null): array
    {
        $data = $request->safe()->except([
            'main_image',
            'remove_main_image',
            'gallery_images',
            'variants',
            'deleted_image_ids',
            'deleted_variant_ids',
        ]);

        $data['stock'] = ($data['type'] ?? 'simple') === 'simple' ? ($data['stock'] ?? 0) : null;

        if ($request->boolean('remove_main_image') && $product?->main_image && ! $request->hasFile('main_image')) {
            Storage::disk('public')->delete($product->main_image);
            $data['main_image'] = null;
        }

        if ($request->hasFile('main_image')) {
            if ($product?->main_image) {
                Storage::disk('public')->delete($product->main_image);
            }
            $data['main_image'] = $request->file('main_image')->store('products/main', 'public');
        }

        return $data;
    }

    private function storeGalleryImages(Request $request, Product $product): void
    {
        foreach ($request->file('gallery_images', []) as $index => $image) {
            $product->images()->create([
                'image' => $image->store('products/gallery', 'public'),
                'is_main' => false,
                'sort_order' => $index,
            ]);
        }
    }

    private function syncVariants(Request $request, Product $product): void
    {
        if ($product->type !== 'variable') {
            foreach ($product->variants as $variant) {
                if ($variant->image) {
                    Storage::disk('public')->delete($variant->image);
                }
                $variant->delete();
            }

            return;
        }

        foreach ($request->input('variants', []) as $index => $variantData) {
            $variant = filled($variantData['id'] ?? null)
                ? $product->variants()->whereKey($variantData['id'])->firstOrFail()
                : $product->variants()->make();

            $variant->fill([
                'sku' => $variantData['sku'] ?? null,
                'price' => $variantData['price'] ?? null,
                'discount_price' => $variantData['discount_price'] ?? null,
                'stock' => $variantData['stock'] ?? 0,
            ]);
            $variant->save();

            $image = $request->file("variants.{$index}.image");
            if ($image) {
                if ($variant->image) {
                    Storage::disk('public')->delete($variant->image);
                }
                $variant->update(['image' => $image->store('products/gallery', 'public')]);
            }

            $variant->attributeValues()->sync($variantData['attribute_values'] ?? []);
        }
    }

    private function deleteImages(Request $request, Product $product): void
    {
        $images = $product->images()->whereIn('id', $request->input('deleted_image_ids', []))->get();

        foreach ($images as $image) {
            Storage::disk('public')->delete($image->image);
            $image->delete();
        }
    }

    private function deleteVariants(Request $request, Product $product): void
    {
        $variants = $product->variants()->whereIn('id', $request->input('deleted_variant_ids', []))->get();

        foreach ($variants as $variant) {
            if ($variant->image) {
                Storage::disk('public')->delete($variant->image);
            }
            $variant->delete();
        }
    }

    private function formatProduct(Product $product, bool $full = false): array
    {
        $data = [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'meta_title' => $product->meta_title,
            'meta_description' => $product->meta_description,
            'meta_keywords' => $product->meta_keywords,
            'canonical_url' => $product->canonical_url,
            'seo_index' => $product->seo_index,
            'seo_follow' => $product->seo_follow,
            'description' => $product->description,
            'brand_id' => $product->brand_id,
            'category_id' => $product->category_id,
            'brand' => $product->brand,
            'category' => $product->category,
            'material' => $product->material,
            'origin' => $product->origin,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'price' => $product->price,
            'discount_price' => $product->discount_price,
            'currency' => $product->currency,
            'main_image' => $product->main_image,
            'main_image_url' => $product->main_image ? Storage::url($product->main_image) : null,
            'status' => $product->status,
            'type' => $product->type,
            'stock' => $product->stock,
        ];

        if ($full) {
            $data['images'] = $product->images->map(fn (ProductImage $image) => [
                'id' => $image->id,
                'image' => $image->image,
                'url' => Storage::url($image->image),
                'is_main' => $image->is_main,
                'sort_order' => $image->sort_order,
            ])->values();
            $data['variants'] = $product->variants->map(fn (ProductVariant $variant) => [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'price' => $variant->price,
                'discount_price' => $variant->discount_price,
                'stock' => $variant->stock,
                'image' => $variant->image,
                'image_url' => $variant->image ? Storage::url($variant->image) : null,
                'attribute_values' => $variant->attributeValues->pluck('id')->values(),
            ])->values();
        }

        return $data;
    }
}
