<?php

namespace App\Http\Controllers\Frontend\Profile;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class WishlistController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        $customerId = $request->session()->get('customer_id');

        if (! $customerId) {
            return redirect('/')
                ->with('message', 'برای مشاهده علاقه‌مندی‌ها ابتدا وارد حساب کاربری شوید.');
        }

        $customer = Customer::query()
            ->select(['id', 'name', 'phone'])
            ->findOrFail($customerId);

        $products = $customer->wishlistProducts()
            ->with(['brand:id,name,slug', 'category:id,name,slug', 'mainImage'])
            ->withPivot('created_at')
            ->latest('customer_wishlists.created_at')
            ->paginate(12)
            ->withQueryString()
            ->through(fn (Product $product) => $this->productCardPayload($product));

        return Inertia::render('Frontend/Profile/Wishlist/Index', [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
            ],
            'products' => $products,
        ]);
    }

    private function productCardPayload(Product $product): array
    {
        $image = $product->mainImage->first()?->url
            ?: ($product->main_image ? Storage::url($product->main_image) : null);

        return [
            'id' => $product->id,
            'slug' => $product->slug,
            'name' => $product->name,
            'brand' => $product->brand?->name ?? '-',
            'category' => $product->category?->name,
            'feature' => $product->material ?: ($product->origin ?: 'کیفیت تضمین‌شده فروشگاه'),
            'price' => (float) ($product->discount_price ?: $product->price),
            'oldPrice' => $product->discount_price ? (float) $product->price : null,
            'stock' => (int) ($product->stock ?? 0),
            'inStock' => (int) ($product->stock ?? 0) > 0,
            'isNew' => $product->created_at?->gt(now()->subDays(10)) ?? false,
            'image' => $image,
            'is_wishlisted' => true,
        ];
    }
}
