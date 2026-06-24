<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(): Response
    {
        $featuredProducts = Product::query()
            ->where('status', 'active')
            ->where('is_featured', true)
            ->with(['brand', 'category', 'mainImage'])
            ->latest()
            ->take(12)
            ->get()
            ->map(fn (Product $product) => $this->formatProduct($product))
            ->values();

        return Inertia::render('Frontend/Home', [
            'featuredProducts' => $featuredProducts,
        ]);
    }

    private function formatProduct(Product $product): array
    {
        $price = $product->discount_price ?: $product->price;
        $image = $product->mainImage->first()?->url
            ?: ($product->main_image ? Storage::url($product->main_image) : null);

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'brand' => $product->brand?->name,
            'category' => $product->category?->name,
            'description' => $product->meta_description
                ?: str($product->description ?? 'محصول منتخب فروشگاه MotoPart')->stripTags()->limit(90)->toString(),
            'price' => $price ? Number_format((float) $price) . ' تومان' : 'تماس بگیرید',
            'image' => $image,
            'icon' => $product->brand?->name
                ? mb_substr($product->brand->name, 0, 2)
                : 'MP',
            'is_original' => $product->is_original,
        ];
    }
}
