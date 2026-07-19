<?php

namespace App\Http\Services;

use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class HomeProductRankingService
{
    public function rankings(?int $customerId = null): array
    {
        return [
            'best_sellers' => $this->bestSellers($customerId),
            'most_viewed' => $this->mostViewed($customerId),
            'most_reviewed' => $this->mostReviewed($customerId),
        ];
    }

    private function bestSellers(?int $customerId): array
    {
        return $this->baseQuery($customerId)
            ->withSum('orderItems as sold_count', 'quantity')
            ->orderByDesc('sold_count')
            ->latest('id')
            ->limit(12)
            ->get()
            ->map(fn (Product $product) => ProductResource::make($product)->resolve())
            ->values()
            ->all();
    }

    private function mostViewed(?int $customerId): array
    {
        return $this->baseQuery($customerId)
            ->when(
                Schema::hasColumn('products', 'views'),
                fn (Builder $query) => $query->orderByDesc('views'),
                fn (Builder $query) => $query->latest('id'),
            )
            ->latest('id')
            ->limit(12)
            ->get()
            ->map(fn (Product $product) => ProductResource::make($product)->resolve())
            ->values()
            ->all();
    }

    private function mostReviewed(?int $customerId): array
    {
        return $this->baseQuery($customerId)
            ->withCount([
                'reviews as approved_reviews_count' => fn (Builder $query) => $query->where('status', 'approved'),
            ])
            ->orderByDesc('approved_reviews_count')
            ->latest('id')
            ->limit(12)
            ->get()
            ->map(fn (Product $product) => ProductResource::make($product)->resolve())
            ->values()
            ->all();
    }

    private function baseQuery(?int $customerId): Builder
    {
        return Product::query()
            ->where('status', 'active')
            ->with(['brand:id,name,slug', 'category:id,name,slug', 'mainImage'])
            ->when($customerId, fn (Builder $builder) => $builder->withExists([
                'wishlistedByCustomers as is_wishlisted' => fn (Builder $wishlist) => $wishlist->where('customers.id', $customerId),
            ]));
    }
}
