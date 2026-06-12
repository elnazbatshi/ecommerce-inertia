<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\FrontendCartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function sync(Request $request, FrontendCartService $carts): JsonResponse
    {
        $customerId = $request->session()->get('customer_id');

        if (! $customerId) {
            throw ValidationException::withMessages([
                'customer' => 'برای ادامه خرید ابتدا وارد حساب مشتری شوید.',
            ]);
        }

        $data = $request->validate([
            'items' => ['nullable', 'array'],
            'items.*.id' => ['nullable', 'integer'],
            'items.*.product_id' => ['nullable', 'integer'],
            'items.*.variant_id' => ['nullable', 'integer'],
            'items.*.product_variant_id' => ['nullable', 'integer'],
            'items.*.quantity' => ['required_with:items', 'integer', 'min:1'],
        ]);

        $customer = Customer::findOrFail($customerId);
        $cart = $carts->sync($customer, $data['items'] ?? [], $request->session()->getId());

        return response()->json([
            'cart' => $carts->serialize($cart),
        ]);
    }
}
