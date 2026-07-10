<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\CustomerWishlist;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function toggle(Request $request, Product $product): JsonResponse
    {
        $customerId = $request->session()->get('customer_id');

        if (! $customerId) {
            return response()->json([
                'message' => 'برای افزودن محصول به علاقه‌مندی‌ها ابتدا وارد حساب کاربری شوید.',
            ], 401);
        }

        $wishlist = CustomerWishlist::query()
            ->where('customer_id', $customerId)
            ->where('product_id', $product->id)
            ->first();

        if ($wishlist) {
            $wishlist->delete();

            return response()->json([
                'is_wishlisted' => false,
                'message' => 'محصول از علاقه‌مندی‌ها حذف شد.',
            ]);
        }

        CustomerWishlist::query()->create([
            'customer_id' => $customerId,
            'product_id' => $product->id,
        ]);

        return response()->json([
            'is_wishlisted' => true,
            'message' => 'محصول به علاقه‌مندی‌ها اضافه شد.',
        ]);
    }
}
