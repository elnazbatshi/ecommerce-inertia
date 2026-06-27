<?php

namespace App\Http\Controllers\Frontend\Profile;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        $customerId = $request->session()->get('customer_id');

        if (! $customerId) {
            return redirect('/')
                ->with('message', 'برای مشاهده سفارش‌ها ابتدا وارد حساب کاربری شوید.');
        }

        $customer = Customer::query()
            ->select(['id', 'name', 'phone'])
            ->findOrFail($customerId);

        $orders = Order::query()
            ->where('customer_id', $customer->id)
            ->with(['items', 'shippingMethod', 'paymentMethod', 'payments'])
            ->latest()
            ->paginate(50)
            ->withQueryString()
            ->through(fn (Order $order) => [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status,
                'payment_status' => $order->payment_status,
                'total' => $order->total,
                'created_at' => $order->created_at?->toIso8601String(),
                'item_count' => $order->items->sum('quantity'),
                'shipping_method_name' => $order->shipping_method_name
                    ?: $order->shippingMethod?->name,
                'payment_method_name' => $order->payment_method_name
                    ?: $order->paymentMethod?->name,
                'items_preview' => $order->items
                    ->take(3)
                    ->map(fn ($item) => [
                        'name' => $item->product_name,
                        'quantity' => $item->quantity,
                        'variant_name' => $item->variant_name,
                        'total_price' => $item->total_price,
                    ])
                    ->values(),
            ]);

        return Inertia::render('Frontend/Profile/Orders/Index', [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
            ],
            'orders' => $orders,
        ]);
    }
}
