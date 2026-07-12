<?php

namespace App\Http\Services;

use App\Http\Resources\ProductResource;
use App\Models\Attribute;
use App\Models\Brand;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Vehicle;
use App\Support\Pagination;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductService
{
    public function paginated(Request $request)
    {
        $sortField = in_array($request->input('sortField'), config('shop.products.sortable_fields', []), true)
            ? $request->input('sortField')
            : 'id';
        $sortDirection = $request->input('sortOrder') === 'asc' ? 'asc' : 'desc';

        return Product::query()
            ->with(['category:id,name', 'brand:id,name'])
            ->search($request->string('search')->toString())
            ->filter($request->only(['category_id', 'brand_id', 'status', 'type', 'is_featured']))
            ->orderBy($sortField, $sortDirection)
            ->paginate(Pagination::perPage($request))
            ->withQueryString()
            ->through(fn (Product $product) => ProductResource::make($product)->resolve());
    }

    public function categoryOptions()
    {
        return Category::query()->select(['id', 'name', 'slug'])->orderBy('name')->get();
    }

    public function brandOptions()
    {
        return Brand::query()->select(['id', 'name', 'slug'])->orderBy('name')->get();
    }

    public function attributeOptions()
    {
        return Attribute::query()->with('values:id,attribute_id,value')->orderBy('name')->get();
    }

    public function publishedPostOptions()
    {
        return BlogPost::query()->select(['id', 'title', 'slug'])->published()->latest('published_at')->get();
    }
    public function vehicleOptions()
    {
        return Vehicle::query()
            ->with('brand.vehicleType:id,name,slug')
            ->active()
            ->ordered()
            ->get(['id', 'vehicle_brand_id', 'name', 'type']);
    }

    public function create(Request $request): Product
    {
        return DB::transaction(function () use ($request) {
            $product = Product::create($this->payload($request));
            $product->relatedPosts()->sync($request->input('related_post_ids', []));
            $product->vehicles()->sync($request->input('vehicle_ids', []));
            $this->storeGalleryImages($request, $product);
            $this->syncVariants($request, $product);

            return $product;
        });
    }

    public function update(Request $request, Product $product): Product
    {
        return DB::transaction(function () use ($request, $product) {
            $product->update($this->payload($request, $product));
            $product->relatedPosts()->sync($request->input('related_post_ids', []));
            $product->vehicles()->sync($request->input('vehicle_ids', []));
            $this->deleteImages($request, $product);
            $this->storeGalleryImages($request, $product);
            $this->deleteVariants($request, $product);
            $this->syncVariants($request, $product);

            return $product->refresh();
        });
    }

    public function deleteImage(ProductImage $image): void
    {
        $this->deleteFile($image->image);
        $image->delete();
    }

    public function deleteVariant(ProductVariant $variant): void
    {
        $this->deleteFile($variant->image);
        $variant->delete();
    }

    private function payload(Request $request, ?Product $product = null): array
    {
        $data = $request->safe()->except([
            'main_image',
            'remove_main_image',
            'gallery_images',
            'variants',
            'deleted_image_ids',
            'deleted_variant_ids',
            'related_post_ids',
        ]);

        $data['stock'] = ($data['type'] ?? 'simple') === 'simple' ? ($data['stock'] ?? 0) : null;

        if ($request->boolean('remove_main_image') && $product?->main_image && ! $request->filled('main_image')) {
            $this->deleteFile($product->main_image);
            $data['main_image'] = null;
        }

        if ($request->filled('main_image')) {
            $this->deleteFile($product?->main_image);
            $data['main_image'] = $this->mediaPath($request->input('main_image'));
        }

        return $data;
    }

    private function storeGalleryImages(Request $request, Product $product): void
    {
        foreach (array_filter($request->input('gallery_images', [])) as $index => $mediaId) {
            $product->images()->create([
                'image' => $this->mediaPath($mediaId),
                'is_main' => false,
                'sort_order' => $index,
            ]);
        }
    }

    private function syncVariants(Request $request, Product $product): void
    {
        if ($product->type !== 'variable') {
            foreach ($product->variants as $variant) {
                $this->deleteVariant($variant);
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

            $imageMediaId = $variantData['image'] ?? null;
            if ($request->boolean("variants.{$index}.remove_image") && $variant->image && ! $imageMediaId) {
                $this->deleteFile($variant->image);
                $variant->update(['image' => null]);
            } elseif ($imageMediaId) {
                $this->deleteFile($variant->image);
                $variant->update(['image' => $this->mediaPath($imageMediaId)]);
            }

            $variant->attributeValues()->sync($variantData['attribute_values'] ?? []);
        }
    }

    private function deleteImages(Request $request, Product $product): void
    {
        $product->images()
            ->whereIn('id', $request->input('deleted_image_ids', []))
            ->get()
            ->each(fn (ProductImage $image) => $this->deleteImage($image));
    }

    private function deleteVariants(Request $request, Product $product): void
    {
        $product->variants()
            ->whereIn('id', $request->input('deleted_variant_ids', []))
            ->get()
            ->each(fn (ProductVariant $variant) => $this->deleteVariant($variant));
    }

    private function deleteFile(?string $path): void
    {
        if ($path) {
            if (Media::query()->where('path', $path)->exists()) {
                return;
            }

            Storage::disk($this->disk())->delete($path);
        }
    }

    private function mediaPath(int|string $mediaId): string
    {
        return Media::query()->findOrFail($mediaId)->path;
    }

    private function disk(): string
    {
        return (string) config('shop.uploads.disk', 'public');
    }

    private function mainPath(): string
    {
        return (string) config('shop.uploads.products_main_path', 'products/main');
    }

    private function galleryPath(): string
    {
        return (string) config('shop.uploads.products_gallery_path', 'products/gallery');
    }
}
