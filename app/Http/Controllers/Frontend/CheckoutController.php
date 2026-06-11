<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Services\OrderInventoryService;
use App\Models\Address;
use App\Models\City;
use App\Models\Customer;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Province;
use App\Models\ShippingMethod;
use App\Services\FrontendCartService;
use App\Services\FrontendPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    public function index(Request $request, FrontendCartService $carts): RedirectResponse|Response
    {
        $customer = $this->customer($request);
        if (! $customer) {
            return redirect()->route('site.cart');
        }

        $cart = $carts->activeCartFor($customer);
        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('site.cart');
        }

        return Inertia::render('Frontend/Checkout/Index', [
            'cart' => $carts->serialize($cart),
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'phone' => $customer->phone,
            ],
            'shippingMethods' => ShippingMethod::active()->ordered()->get()->map(fn (ShippingMethod $method) => [
                'id' => $method->id,
                'name' => $method->name,
                'description' => $method->description,
                'base_cost' => (float) $method->base_cost,
                'free_from_amount' => $method->free_from_amount ? (float) $method->free_from_amount : null,
                'min_order_amount' => $method->min_order_amount ? (float) $method->min_order_amount : null,
                'max_order_amount' => $method->max_order_amount ? (float) $method->max_order_amount : null,
                'estimated_delivery_days' => $method->estimated_delivery_days,
            ]),
            'paymentMethods' => PaymentMethod::active()->ordered()->get()->map(fn (PaymentMethod $method) => [
                'id' => $method->id,
                'name' => $method->name,
                'description' => $method->description,
                'driver' => $method->driver,
                'fee_type' => $method->fee_type,
                'fee_value' => (float) $method->fee_value,
            ]),
            'provinces' => Province::active()
                ->ordered()
                ->with(['cities' => fn ($query) => $query->active()->ordered()])
                ->get()
                ->map(fn (Province $province) => [
                    'id' => $province->id,
                    'name' => $province->name,
                    'cities' => $province->cities->map(fn (City $city) => [
                        'id' => $city->id,
                        'name' => $city->name,
                    ])->values(),
                ]),
            'addresses' => $customer->addresses()
                ->with(['province:id,name', 'city:id,name'])
                ->latest('is_default')
                ->latest()
                ->get()
                ->map(fn (Address $address) => [
                    'id' => $address->id,
                    'title' => $address->title,
                    'receiver_name' => $address->receiver_name,
                    'receiver_phone' => $address->receiver_phone,
                    'province_id' => $address->province_id,
                    'city_id' => $address->city_id,
                    'province' => $address->province?->name ?: $address->province,
                    'city' => $address->city?->name ?: $address->city,
                    'postal_code' => $address->postal_code,
                    'address' => $address->address,
                    'is_default' => $address->is_default,
                ]),
        ]);
    }

    public function store(Request $request, FrontendCartService $carts, OrderInventoryService $inventory, FrontendPaymentService $payments): RedirectResponse
    {
        $customer = $this->customer($request);
        if (! $customer) {
            return redirect()->route('site.cart');
        }

        $data = $request->validate([
            'province_id' => ['required', 'exists:provinces,id'],
            'city_id' => ['required', 'exists:cities,id'],
            'address' => ['required', 'string', 'max:2000'],
            'postal_code' => ['nullable', 'string', 'max:32'],
            'recipient_name' => ['required', 'string', 'max:255'],
            'recipient_mobile' => ['required', 'string', 'max:32'],
            'shipping_method_id' => [
                'required',
                Rule::exists('shipping_methods', 'id')->where(fn ($query) => $query->whereNull('deleted_at')->where('is_active', true)),
            ],
            'payment_method_id' => [
                'required',
                Rule::exists('payment_methods', 'id')->where(fn ($query) => $query->whereNull('deleted_at')->where('is_active', true)),
            ],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        [$order, $payment] = DB::transaction(function () use ($customer, $data, $carts, $inventory, $payments) {
            $cart = $carts->activeCartFor($customer);
            if (! $cart || $cart->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'cart' => 'سبد خرید شما خالی است.',
                ]);
            }

            $cart->load(['items.product', 'items.productVariant.attributeValues.attribute']);

            $shippingMethod = ShippingMethod::active()->findOrFail($data['shipping_method_id']);
            $paymentMethod = PaymentMethod::active()->findOrFail($data['payment_method_id']);
            $province = Province::findOrFail($data['province_id']);
            $city = City::where('province_id', $province->id)->findOrFail($data['city_id']);
            $orderNumber = $this->generateOrderNumber();
            $subtotal = $carts->subtotal($cart);
            $shippingCost = $this->shippingCost($shippingMethod, $subtotal);
            $total = $subtotal + $shippingCost;

            $address = Address::create([
                'customer_id' => $customer->id,
                'title' => 'آدرس سفارش',
                'receiver_name' => $data['recipient_name'],
                'receiver_phone' => $data['recipient_mobile'],
                'province_id' => $province->id,
                'city_id' => $city->id,
                'province' => $province->name,
                'city' => $city->name,
                'postal_code' => $data['postal_code'] ?? null,
                'address' => $data['address'],
                'is_default' => ! $customer->addresses()->exists(),
            ]);

            $order = Order::create([
                'order_number' => $orderNumber,
                'customer_id' => $customer->id,
                'address_id' => $address->id,
                'shipping_method_id' => $shippingMethod->id,
                'payment_method_id' => $paymentMethod->id,
                'shipping_receiver_name' => $data['recipient_name'],
                'shipping_receiver_phone' => $data['recipient_mobile'],
                'shipping_province_name' => $province->name,
                'shipping_city_name' => $city->name,
                'shipping_address' => $data['address'],
                'shipping_postal_code' => $data['postal_code'] ?? null,
                'shipping_method_name' => $shippingMethod->name,
                'payment_method_name' => $paymentMethod->name,
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'subtotal' => $subtotal,
                'discount_total' => 0,
                'shipping_cost' => $shippingCost,
                'tax_total' => 0,
                'total' => $total,
                'customer_note' => $data['note'] ?? null,
            ]);

            foreach ($cart->items as $cartItem) {
                $unitPrice = $carts->itemUnitPrice($cartItem);

                $order->items()->create([
                    'product_id' => $cartItem->product_id,
                    'product_variant_id' => $cartItem->product_variant_id,
                    'product_name' => $cartItem->product->name,
                    'variant_name' => $carts->variantLabel($cartItem->productVariant),
                    'sku' => $cartItem->productVariant?->sku ?: $cartItem->product->sku,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $unitPrice,
                    'discount_price' => null,
                    'total_price' => $unitPrice * $cartItem->quantity,
                ]);
            }

            $inventory->reserve($order->refresh());
            $cart->update(['status' => 'ordered']);
            $payment = $payments->createPendingPayment($order->refresh());

            return [$order->refresh(), $payment];
        });

        if ($payments->isOnline($payment)) {
            return redirect()->route('frontend.payments.start', $payment);
        }

        return redirect()->route('frontend.orders.thank-you', $order);
    }

    private function customer(Request $request): ?Customer
    {
        $customerId = $request->session()->get('customer_id');

        return $customerId ? Customer::find($customerId) : null;
    }

    private function shippingCost(ShippingMethod $method, float $subtotal): float
    {
        if ($method->free_from_amount && $subtotal >= (float) $method->free_from_amount) {
            return 0;
        }

        return (float) $method->base_cost;
    }

    private function generateOrderNumber(): string
    {
        $prefix = now()->format('Ymd');
        $count = Order::query()->where('order_number', 'like', "MP-{$prefix}-%")->lockForUpdate()->count() + 1;

        do {
            $number = 'MP-' . $prefix . '-' . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
            $count++;
        } while (Order::query()->where('order_number', $number)->exists());

        return $number;
    }
}
