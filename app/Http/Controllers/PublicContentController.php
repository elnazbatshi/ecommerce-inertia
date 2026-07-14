<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductReview;
use App\Models\ProductVariant;
use App\Services\ProductArchiveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PublicContentController extends Controller
{
    public function __construct(private readonly ProductArchiveService $archiveService) {}

    public function products(Request $request): Response
    {
        return Inertia::render('Frontend/Products/Index', [
            ...$this->archiveService->archive(Product::query(), $request),
        ]);
    }

    public function product(Product $product, Request $request): Response
    {
        abort_unless($product->status === 'active', 404);

        $product->load([
            'category:id,name,slug',
            'brand:id,name,slug',
            'images',
            'variants.attributeValues.attribute',
            'vehicles.brand.vehicleType:id,name,slug',
            'reviews' => fn ($query) => $query->approved()->with('customer:id,name,phone')->latest(),
        ]);
        $customerId = $request->session()->get('customer_id');

        $relatedProducts = Product::query()
            ->with(['brand:id,name,slug', 'category:id,name,slug'])
            ->when($customerId, fn ($builder) => $builder->withExists([
                'wishlistedByCustomers as is_wishlisted' => fn ($wishlist) => $wishlist->where('customers.id', $customerId),
            ]))
            ->where('status', 'active')
            ->whereKeyNot($product->id)
            ->where(function ($query) use ($product) {
                $query
                    ->when($product->category_id, fn ($builder) => $builder->where('category_id', $product->category_id))
                    ->when($product->brand_id, fn ($builder) => $builder->orWhere('brand_id', $product->brand_id));
            })
            ->latest('id')
            ->limit(8)
            ->get()
            ->map(fn (Product $item) => $this->productCardPayload($item))
            ->values();

        return Inertia::render('Frontend/Products/Show', [
            'product' => $this->productDetailPayload($product),
            'relatedProducts' => $relatedProducts,
            'reviewSummary' => $this->reviewSummary($product),
            'reviews' => $product->reviews->map(fn (ProductReview $review) => $this->reviewPayload($review))->values(),
            'customerReview' => $this->customerReviewPayload($product, $request),
        ]);
    }

    public function category(Category $category, Request $request): Response
    {
        $archive = $this->archiveService->archive(
            Product::query()->where('category_id', $category->id),
            $request
        );

        return Inertia::render('Frontend/Categories/Show', [
            ...$archive,
            'category' => $category->only(['id', 'name', 'slug', 'meta_description']),
        ]);
    }

    public function brand(Brand $brand, Request $request): Response
    {
        $archive = $this->archiveService->archive(
            Product::query()->where('brand_id', $brand->id),
            $request
        );

        return Inertia::render('Frontend/Brands/Show', [
            ...$archive,
            'brand' => $brand->only(['id', 'name', 'slug', 'description', 'logo']),
        ]);
    }

    public function page(Page $page): Response
    {
        abort_unless($page->status === 'published', 404);

        return Inertia::render('Frontend/Pages/Show', [
            'page' => [
                'id' => $page->id,
                'title' => $page->title,
                'slug' => $page->slug,
                'content' => $page->content,
                'featured_image' => $page->featured_image,
                'featured_image_url' => $page->featured_image ? Storage::url($page->featured_image) : null,
                'template' => $page->template,
                'meta_title' => $page->meta_title,
                'meta_description' => $page->meta_description,
                'canonical_url' => $page->canonical_url,
                'seo_index' => $page->seo_index,
                'seo_follow' => $page->seo_follow,
            ],
        ]);
    }

    private function productDetailPayload(Product $product): array
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'meta_title' => $product->meta_title,
            'meta_description' => $product->meta_description,
            'canonical_url' => $product->canonical_url,
            'seo_index' => $product->seo_index,
            'seo_follow' => $product->seo_follow,
            'description' => $product->description,
            'short_description' => $product->meta_description,
            'brand' => $product->brand ? $product->brand->only(['id', 'name', 'slug']) : null,
            'category' => $product->category ? $product->category->only(['id', 'name', 'slug']) : null,
            'material' => $product->material,
            'origin' => $product->origin,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'price' => (float) $product->price,
            'discount_price' => $product->discount_price !== null ? (float) $product->discount_price : null,
            'currency' => $product->currency,
            'status' => $product->status,
            'type' => $product->type,
            'is_original' => $product->is_original,
            'stock' => (int) ($product->stock ?? 0),
            'gallery' => $this->productGallery($product),
            'variants' => $product->variants->map(fn (ProductVariant $variant) => [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'price' => $variant->price !== null ? (float) $variant->price : (float) $product->price,
                'discount_price' => $variant->discount_price !== null ? (float) $variant->discount_price : null,
                'stock' => (int) ($variant->stock ?? 0),
                'image_url' => $variant->image ? Storage::url($variant->image) : null,
                'active' => true,
                'label' => $this->variantLabel($variant),
                'attributes' => $variant->attributeValues->map(fn ($value) => [
                    'name' => $value->attribute?->name,
                    'value' => $value->value,
                ])->values(),
            ])->values(),
            'vehicles' => $product->vehicles->map(fn ($vehicle) => [
                'id' => $vehicle->id,
                'type' => $vehicle->brand?->vehicleType?->slug ?? $vehicle->type,
                'type_name' => $vehicle->brand?->vehicleType?->name,
                'name' => $vehicle->name,
                'slug' => $vehicle->slug,
                'brand' => $vehicle->brand?->name,
                'trim' => $vehicle->trim,
                'engine' => $vehicle->engine,
                'year_from' => $vehicle->year_from,
                'year_to' => $vehicle->year_to,
            ])->values(),
            'specs' => $this->productSpecs($product),
        ];
    }

    private function reviewSummary(Product $product): array
    {
        $approved = $product->reviews;
        $average = round((float) $approved->avg('rating'), 1);

        return [
            'average' => $average,
            'count' => $approved->count(),
            'stars' => collect(range(5, 1))->map(fn (int $star) => [
                'star' => $star,
                'count' => $approved->where('rating', $star)->count(),
            ])->values(),
        ];
    }

    private function reviewPayload(ProductReview $review): array
    {
        return [
            'id' => $review->id,
            'rating' => $review->rating,
            'title' => $review->title,
            'comment' => $review->comment,
            'is_buyer' => $review->is_buyer,
            'created_at' => $review->created_at?->toDateTimeString(),
            'customer_name' => $review->customer?->name ?: $this->maskedPhone($review->customer?->phone),
        ];
    }

    private function customerReviewPayload(Product $product, Request $request): ?array
    {
        $customerId = $request->session()->get('customer_id');

        if (! $customerId) {
            return null;
        }

        $review = ProductReview::query()
            ->where('product_id', $product->id)
            ->where('customer_id', $customerId)
            ->first();

        if (! $review) {
            return null;
        }

        return [
            'id' => $review->id,
            'rating' => $review->rating,
            'title' => $review->title,
            'comment' => $review->comment,
            'status' => $review->status,
            'is_buyer' => $review->is_buyer,
        ];
    }

    private function maskedPhone(?string $phone): string
    {
        if (! $phone || strlen($phone) < 7) {
            return 'مشتری MotoPart';
        }

        return substr($phone, 0, 4) . '***' . substr($phone, -4);
    }

    private function productGallery(Product $product): array
    {
        $items = collect();

        if ($product->main_image) {
            $items->push([
                'id' => 'main',
                'url' => Storage::url($product->main_image),
                'alt' => $product->name,
                'is_main' => true,
            ]);
        }

        $product->images->each(function (ProductImage $image) use ($items, $product) {
            $items->push([
                'id' => $image->id,
                'url' => Storage::url($image->image),
                'alt' => $product->name,
                'is_main' => $image->is_main,
            ]);
        });

        return $items->unique('url')->values()->all();
    }

    private function productSpecs(Product $product): array
    {
        return collect([
            ['group' => 'مشخصات اصلی', 'name' => 'SKU', 'value' => $product->sku],
            ['group' => 'مشخصات اصلی', 'name' => 'بارکد', 'value' => $product->barcode],
            ['group' => 'مشخصات فنی', 'name' => 'جنس / متریال', 'value' => $product->material],
            ['group' => 'مشخصات فنی', 'name' => 'کشور سازنده', 'value' => $product->origin],
            ['group' => 'مشخصات فروش', 'name' => 'نوع محصول', 'value' => $product->type],
            ['group' => 'مشخصات فروش', 'name' => 'واحد پول', 'value' => $product->currency],
        ])->filter(fn ($spec) => filled($spec['value']))->values()->all();
    }

    private function productCardPayload(Product $product): array
    {
        return [
            'id' => $product->id,
            'slug' => $product->slug,
            'image' => $product->main_image ? Storage::url($product->main_image) : 'https://picsum.photos/seed/product-' . $product->id . '/600/420',
            'brand' => $product->brand?->name ?? '-',
            'name' => $product->name,
            'feature' => $product->material ?: ($product->origin ?: 'کیفیت تضمین‌شده فروشگاه'),
            'price' => (float) ($product->discount_price ?: $product->price),
            'oldPrice' => $product->discount_price ? (float) $product->price : null,
            'inStock' => (int) ($product->stock ?? 0) > 0,
            'isNew' => $product->created_at?->gt(now()->subDays(10)) ?? false,
            'is_wishlisted' => (bool) ($product->is_wishlisted ?? false),
        ];
    }

    private function variantLabel(ProductVariant $variant): string
    {
        $attributes = $variant->attributeValues
            ->map(fn ($value) => trim(($value->attribute?->name ? $value->attribute->name . ': ' : '') . $value->value))
            ->filter()
            ->join(' / ');

        return $attributes ?: ($variant->sku ?: "تنوع #{$variant->id}");
    }
}
