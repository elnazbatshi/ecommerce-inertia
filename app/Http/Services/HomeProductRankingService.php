<?php

namespace App\Http\Services;

use App\Http\Resources\ProductResource;
use App\Models\CustomerWishlist;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class HomeProductRankingService
{
    public function rankings(?int $customerId = null, int $limit = 12): array
    {
        return [
            'best_sellers' => $this->bestSellers($limit, $customerId),
            'most_viewed' => $this->mostViewed($limit, $customerId),
            'most_reviewed' => $this->mostReviewed($limit, $customerId),
        ];
    }

    public function bestSellers(int $limit = 12, ?int $customerId = null): array
    {
        $products = Cache::remember("home_products:best_sellers:{$limit}", now()->addMinutes(10), function () use ($limit) {
            $products = $this->baseQuery()
                ->withSum([
                    'orderItems as sold_count' => fn (Builder $query) => $query->whereHas('order', fn (Builder $order) => $order
                        ->where('payment_status', 'paid')
                        ->whereIn('status', ['processing', 'shipped', 'delivered'])),
                ], 'quantity')
                ->having('sold_count', '>', 0)
                ->orderByDesc('sold_count')
                ->latest('products.created_at')
                ->limit($limit)
                ->get();

            return $this->resolveProducts($products);
        });

        return $this->withWishlistFlags($products, $customerId);
    }

    public function mostViewed(int $limit = 12, ?int $customerId = null): array
    {
        $products = Cache::remember("home_products:most_viewed:{$limit}", now()->addMinutes(10), function () use ($limit) {
            $products = $this->baseQuery()
                ->where('views', '>', 0)
                ->orderByDesc('views')
                ->latest('products.created_at')
                ->limit($limit)
                ->get();

            return $this->resolveProducts($products);
        });

        return $this->withWishlistFlags($products, $customerId);
    }

    public function mostReviewed(int $limit = 12, ?int $customerId = null): array
    {
        $products = Cache::remember("home_products:most_reviewed:{$limit}", now()->addMinutes(10), function () use ($limit) {
            $products = $this->baseQuery()
                ->withCount([
                    'reviews as approved_reviews_count' => fn (Builder $query) => $query->where('status', 'approved'),
                ])
                ->having('approved_reviews_count', '>', 0)
                ->orderByDesc('approved_reviews_count')
                ->latest('products.created_at')
                ->limit($limit)
                ->get();

            return $this->resolveProducts($products);
        });

        return $this->withWishlistFlags($products, $customerId);
    }

    private function baseQuery(): Builder
    {
        return Product::query()
            ->where('status', 'active')
            ->with(['brand:id,name,slug', 'category:id,name,slug', 'mainImage']);
    }

    private function resolveProducts($products): array
    {
        return ProductResource::collection($products)->resolve();
    }

    private function withWishlistFlags(array $products, ?int $customerId): array
    {
        if (! $customerId || empty($products)) {
            return array_map(fn (array $product) => [...$product, 'is_wishlisted' => false], $products);
        }

        $productIds = collect($products)->pluck('id')->filter()->values();
        $wishlistIds = CustomerWishlist::query()
            ->where('customer_id', $customerId)
            ->whereIn('product_id', $productIds)
            ->pluck('product_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return array_map(
            fn (array $product) => [...$product, 'is_wishlisted' => in_array((int) $product['id'], $wishlistIds, true)],
            $products
        );
    }
}
