<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\SearchLog;
use App\Models\SearchSuggestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SiteSearchController extends Controller
{
    public function suggestions(): JsonResponse
    {
        $suggestions = SearchSuggestion::query()
            ->active()
            ->ordered()
            ->limit(12)
            ->get()
            ->map(fn (SearchSuggestion $row) => [
                'title' => $row->title,
                'type' => $row->type,
                'url' => $row->resolveUrl(),
                'icon' => $row->icon,
                'keyword' => $row->keyword,
            ])
            ->values();

        return response()->json(['suggestions' => $suggestions]);
    }

    public function search(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if ($q === '') {
            return response()->json(['products' => [], 'categories' => [], 'brands' => [], 'total' => 0]);
        }

        $products = Product::query()->where('status', 'active')->where('name', 'like', "%{$q}%")->limit(8)->get(['id', 'name', 'slug', 'sku']);
        $categories = Category::query()->where('name', 'like', "%{$q}%")->limit(8)->get(['id', 'name', 'slug']);
        $brands = Brand::query()->where('name', 'like', "%{$q}%")->limit(8)->get(['id', 'name', 'slug']);

        $resultCount = $products->count() + $categories->count() + $brands->count();
        [$type, $matchedType, $matchedId] = $this->resolveSearchType($products, $categories, $brands);

        SearchLog::query()->create([
            'query' => $q,
            'type' => $type,
            'matched_id' => $matchedId,
            'matched_type' => $matchedType,
            'results_count' => $resultCount,
            'user_id' => $request->user()?->id,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
            'searched_at' => Carbon::now(),
        ]);

        return response()->json([
            'products' => $products->map(fn ($item) => ['id' => $item->id, 'title' => $item->name, 'url' => "/products/{$item->slug}"]),
            'categories' => $categories->map(fn ($item) => ['id' => $item->id, 'title' => $item->name, 'url' => "/category/{$item->slug}"]),
            'brands' => $brands->map(fn ($item) => ['id' => $item->id, 'title' => $item->name, 'url' => "/brand/{$item->slug}"]),
            'total' => $resultCount,
        ]);
    }

    private function resolveSearchType($products, $categories, $brands): array
    {
        if ($products->isNotEmpty()) {
            return ['product', 'product', $products->first()->id];
        }
        if ($categories->isNotEmpty()) {
            return ['category', 'category', $categories->first()->id];
        }
        if ($brands->isNotEmpty()) {
            return ['brand', 'brand', $brands->first()->id];
        }

        return ['free_text', null, null];
    }
}
