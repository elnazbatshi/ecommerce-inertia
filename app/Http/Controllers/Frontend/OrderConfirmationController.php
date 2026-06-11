<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderConfirmationController extends Controller
{
    public function show(Request $request, Order $order): RedirectResponse|Response
    {
        $customerId = $request->session()->get('customer_id');
        if (! $customerId || (int) $order->customer_id !== (int) $customerId) {
            abort(403);
        }

        $order->load([
            'items.product',
            'items.productVariant',
            'shippingMethod',
            'paymentMethod',
            'address.province',
            'address.city',
        ]);

        return Inertia::render('Frontend/Orders/ThankYou', [
            'order' => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'shipping_method' => $order->shippingMethod ? [
                    'id' => $order->shippingMethod->id,
                    'name' => $order->shippingMethod->name,
                    'description' => $order->shippingMethod->description,
                ] : null,
                'payment_method' => $order->paymentMethod ? [
                    'id' => $order->paymentMethod->id,
                    'name' => $order->paymentMethod->name,
                    'description' => $order->paymentMethod->description,
                    'driver' => $order->paymentMethod->driver,
                ] : null,
                'address' => $order->address ? [
                    'recipient_name' => $order->shipping_receiver_name,
                    'recipient_mobile' => $order->shipping_receiver_phone,
                    'province' => $order->address->province?->name ?? $order->shipping_province_name,
                    'city' => $order->address->city?->name ?? $order->shipping_city_name,
                    'address' => $order->shipping_address,
                    'postal_code' => $order->shipping_postal_code,
                ] : [
                    'recipient_name' => $order->shipping_receiver_name,
                    'recipient_mobile' => $order->shipping_receiver_phone,
                    'province' => $order->shipping_province_name,
                    'city' => $order->shipping_city_name,
                    'address' => $order->shipping_address,
                    'postal_code' => $order->shipping_postal_code,
                ],
                'subtotal' => (float) $order->subtotal,
                'shipping_cost' => (float) $order->shipping_cost,
                'total' => (float) $order->total,
                'items' => $order->items->map(fn ($item) => [
                    'name' => $item->product_name,
                    'sku' => $item->sku,
                    'variant_name' => $item->variant_name,
                    'quantity' => $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'total_price' => (float) $item->total_price,
                ])->values(),
            ],
        ]);
    }
}
