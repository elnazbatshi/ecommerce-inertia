<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Services\HomeProductRankingService;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __construct(private readonly HomeProductRankingService $rankings)
    {
    }

    public function index(Request $request): Response
    {
        $customerId = $request->session()->get('customer_id');

        $featuredProducts = Product::query()
            ->where('status', 'active')
            ->where('is_featured', true)
            ->with(['brand', 'category', 'mainImage'])
            ->when($customerId, fn (Builder $builder) => $builder->withExists([
                'wishlistedByCustomers as is_wishlisted' => fn (Builder $wishlist) => $wishlist->where('customers.id', $customerId),
            ]))
            ->latest()
            ->take(12)
            ->get()
            ->map(fn (Product $product) => $this->formatProduct($product))
            ->values();

        return Inertia::render('Frontend/Home', [
            'featuredProducts' => $featuredProducts,
            'productRankings' => $this->rankings->rankings($customerId),
        ]);
    }

    private function formatProduct(Product $product): array
    {
        $image = $product->mainImage->first()?->url
            ?: ($product->main_image ? Storage::url($product->main_image) : null);

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'brand' => $product->brand?->name,
            'category' => $product->category?->name,
            'feature' => $product->material ?: ($product->origin ?: 'کیفیت تضمین‌شده فروشگاه'),
            'description' => $product->meta_description
                ?: str($product->description ?? 'محصول منتخب فروشگاه MotoPart')->stripTags()->limit(90)->toString(),
            'price' => (float) ($product->discount_price ?: $product->price),
            'oldPrice' => $product->discount_price ? (float) $product->price : null,
            'stock' => (int) ($product->stock ?? 0),
            'inStock' => (int) ($product->stock ?? 0) > 0,
            'isNew' => $product->created_at?->gt(now()->subDays(10)) ?? false,
            'image' => $image,
            'icon' => $product->brand?->name
                ? mb_substr($product->brand->name, 0, 2)
                : 'MP',
            'is_original' => $product->is_original,
            'is_wishlisted' => (bool) ($product->is_wishlisted ?? false),
        ];
    }
}
