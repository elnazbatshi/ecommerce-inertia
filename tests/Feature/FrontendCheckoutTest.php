<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\City;
use App\Models\Customer;
use App\Models\InventoryLog;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Province;
use App\Models\ShippingMethod;
use App\Http\Services\OrderInventoryService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FrontendCheckoutTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_cart_sync_merges_into_existing_active_customer_cart_without_duplicates(): void
    {
        $customer = $this->customer();
        $product = $this->product(price: 100000, stock: 10);
        $variant = $this->variant($product, price: 120000, stock: 10);

        $firstCart = Cart::create([
            'customer_id' => $customer->id,
            'status' => 'active',
        ]);

        CartItem::create([
            'cart_id' => $firstCart->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]);

        $secondCart = Cart::create([
            'customer_id' => $customer->id,
            'status' => 'active',
        ]);

        CartItem::create([
            'cart_id' => $secondCart->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 1,
        ]);

        $response = $this->withSession(['customer_id' => $customer->id])->postJson('/cart/sync', [
            'items' => [
                ['product_id' => $product->id, 'product_variant_id' => $variant->id, 'quantity' => 2],
            ],
        ]);

        $response->assertOk();

        $this->assertSame(1, Cart::where('customer_id', $customer->id)->where('status', 'active')->count());
        $this->assertSame(1, Cart::where('customer_id', $customer->id)->where('status', 'abandoned')->count());

        $activeCart = Cart::where('customer_id', $customer->id)->where('status', 'active')->with('items')->firstOrFail();
        $this->assertSame(1, $activeCart->items->count());
        $this->assertSame(5, $activeCart->items->first()->quantity);
    }

    public function test_checkout_redirects_to_cart_when_customer_has_no_active_cart(): void
    {
        $customer = $this->customer();

        $this->withSession(['customer_id' => $customer->id])
            ->get('/checkout')
            ->assertRedirect('/cart');
    }

    public function test_checkout_uses_current_database_price_and_marks_cart_ordered(): void
    {
        $customer = $this->customer();
        $province = $this->province();
        $city = $this->city($province);
        $shipping = $this->shippingMethod(baseCost: 50000);
        $payment = $this->paymentMethod();
        $product = $this->product(price: 100000, stock: 10);

        $cart = Cart::create([
            'customer_id' => $customer->id,
            'status' => 'active',
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $product->update(['price' => 150000]);

        $response = $this->withSession(['customer_id' => $customer->id])->post('/checkout', [
            'recipient_name' => 'Ali Ahmadi',
            'recipient_mobile' => '09120000000',
            'province_id' => $province->id,
            'city_id' => $city->id,
            'address' => 'Tehran, Valiasr',
            'postal_code' => '1234567890',
            'shipping_method_id' => $shipping->id,
            'payment_method_id' => $payment->id,
            'note' => 'Handle with care',
        ]);

        $order = Order::query()->latest('id')->firstOrFail();

        $response->assertRedirect(route('frontend.orders.thank-you', $order));
        $this->assertSame('ordered', $cart->fresh()->status);
        $this->assertSame(300000.0, (float) $order->subtotal);
        $this->assertSame(350000.0, (float) $order->total);
        $this->assertSame(1, $order->items()->count());
        $this->assertNotNull($order->stock_reserved_at);
        $this->assertNull($order->stock_released_at);
        $this->assertSame(1, $order->payments()->where('status', 'pending')->count());
        $this->assertSame(8, $product->fresh()->stock);
        $this->assertSame(1, InventoryLog::where('product_id', $product->id)->where('type', 'order')->count());
    }

    public function test_online_checkout_creates_pending_payment_and_redirects_to_payment_start(): void
    {
        [$customer, $province, $city, $shipping, $paymentMethod, $product, $cart] = $this->checkoutFixture('zarinpal');

        $response = $this->withSession(['customer_id' => $customer->id])->post('/checkout', $this->checkoutPayload($province, $city, $shipping, $paymentMethod));

        $order = Order::query()->latest('id')->firstOrFail();
        $payment = $order->payments()->firstOrFail();

        $response->assertRedirect(route('frontend.payments.start', $payment));
        $this->assertSame('ordered', $cart->fresh()->status);
        $this->assertSame('pending', $payment->status);
        $this->assertSame('online', $payment->method);
        $this->assertSame('zarinpal', $payment->gateway);
        $this->assertSame((float) $order->total, (float) $payment->amount);
        $this->assertSame(8, $product->fresh()->stock);
    }

    public function test_non_online_checkout_creates_pending_payment_and_redirects_to_thank_you(): void
    {
        [$customer, $province, $city, $shipping, $paymentMethod] = $this->checkoutFixture('cash_on_delivery');

        $response = $this->withSession(['customer_id' => $customer->id])->post('/checkout', $this->checkoutPayload($province, $city, $shipping, $paymentMethod));

        $order = Order::query()->latest('id')->firstOrFail();
        $payment = $order->payments()->firstOrFail();

        $response->assertRedirect(route('frontend.orders.thank-you', $order));
        $this->assertSame('pending', $payment->status);
        $this->assertSame('cash', $payment->method);
        $this->assertSame('cash_on_delivery', $payment->gateway);
        $this->assertSame('unpaid', $order->payment_status);
    }

    public function test_checkout_rejects_inactive_shipping_method(): void
    {
        $customer = $this->customer();
        $province = $this->province();
        $city = $this->city($province);
        $shipping = $this->shippingMethod(baseCost: 50000, active: false);
        $payment = $this->paymentMethod();
        $product = $this->product(price: 100000, stock: 10);

        $cart = Cart::create([
            'customer_id' => $customer->id,
            'status' => 'active',
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->withSession(['customer_id' => $customer->id])
            ->post('/checkout', [
                'recipient_name' => 'Ali Ahmadi',
                'recipient_mobile' => '09120000000',
                'province_id' => $province->id,
                'city_id' => $city->id,
                'address' => 'Tehran, Valiasr',
                'postal_code' => '1234567890',
                'shipping_method_id' => $shipping->id,
                'payment_method_id' => $payment->id,
                'note' => null,
            ])
            ->assertSessionHasErrors(['shipping_method_id']);
    }

    public function test_checkout_rejects_out_of_stock_products(): void
    {
        $customer = $this->customer();
        $province = $this->province();
        $city = $this->city($province);
        $shipping = $this->shippingMethod(baseCost: 50000);
        $payment = $this->paymentMethod();
        $product = $this->product(price: 100000, stock: 0);
        $orderCount = Order::count();
        $inventoryLogCount = InventoryLog::count();

        $cart = Cart::create([
            'customer_id' => $customer->id,
            'status' => 'active',
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $this->withSession(['customer_id' => $customer->id])
            ->post('/checkout', [
                'recipient_name' => 'Ali Ahmadi',
                'recipient_mobile' => '09120000000',
                'province_id' => $province->id,
                'city_id' => $city->id,
                'address' => 'Tehran, Valiasr',
                'postal_code' => '1234567890',
                'shipping_method_id' => $shipping->id,
                'payment_method_id' => $payment->id,
                'note' => null,
            ])
            ->assertSessionHasErrors();

        $this->assertSame($orderCount, Order::count());
        $this->assertSame(0, $product->fresh()->stock);
        $this->assertSame('active', $cart->fresh()->status);
        $this->assertSame($inventoryLogCount, InventoryLog::count());
    }

    public function test_checkout_reduces_variant_stock_not_product_stock(): void
    {
        $customer = $this->customer();
        $province = $this->province();
        $city = $this->city($province);
        $shipping = $this->shippingMethod(baseCost: 50000);
        $payment = $this->paymentMethod();
        $product = $this->product(price: 100000, stock: 20);
        $variant = $this->variant($product, price: 130000, stock: 6);

        $cart = Cart::create([
            'customer_id' => $customer->id,
            'status' => 'active',
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 3,
        ]);

        $this->withSession(['customer_id' => $customer->id])
            ->post('/checkout', [
                'recipient_name' => 'Ali Ahmadi',
                'recipient_mobile' => '09120000000',
                'province_id' => $province->id,
                'city_id' => $city->id,
                'address' => 'Tehran, Valiasr',
                'postal_code' => '1234567890',
                'shipping_method_id' => $shipping->id,
                'payment_method_id' => $payment->id,
                'note' => null,
            ])
            ->assertRedirect();

        $this->assertSame(20, $product->fresh()->stock);
        $this->assertSame(3, $variant->fresh()->stock);
        $this->assertSame('ordered', $cart->fresh()->status);
        $this->assertSame(1, InventoryLog::where('product_variant_id', $variant->id)->where('type', 'order')->count());
    }

    public function test_checkout_rolls_back_variant_stock_when_quantity_is_not_available(): void
    {
        $customer = $this->customer();
        $province = $this->province();
        $city = $this->city($province);
        $shipping = $this->shippingMethod(baseCost: 50000);
        $payment = $this->paymentMethod();
        $product = $this->product(price: 100000, stock: 20);
        $variant = $this->variant($product, price: 130000, stock: 2);
        $orderCount = Order::count();
        $inventoryLogCount = InventoryLog::count();

        $cart = Cart::create([
            'customer_id' => $customer->id,
            'status' => 'active',
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 3,
        ]);

        $this->withSession(['customer_id' => $customer->id])
            ->post('/checkout', [
                'recipient_name' => 'Ali Ahmadi',
                'recipient_mobile' => '09120000000',
                'province_id' => $province->id,
                'city_id' => $city->id,
                'address' => 'Tehran, Valiasr',
                'postal_code' => '1234567890',
                'shipping_method_id' => $shipping->id,
                'payment_method_id' => $payment->id,
                'note' => null,
            ])
            ->assertSessionHasErrors();

        $this->assertSame($orderCount, Order::count());
        $this->assertSame(2, $variant->fresh()->stock);
        $this->assertSame(20, $product->fresh()->stock);
        $this->assertSame('active', $cart->fresh()->status);
        $this->assertSame($inventoryLogCount, InventoryLog::count());
    }

    public function test_release_pending_unpaid_order_returns_reserved_stock_once(): void
    {
        [$order, $product] = $this->reservedProductOrder(quantity: 2, stock: 10);

        $this->assertSame(8, $product->fresh()->stock);

        app(OrderInventoryService::class)->release($order->fresh());

        $released = $order->fresh();
        $this->assertSame(10, $product->fresh()->stock);
        $this->assertNotNull($released->stock_released_at);
        $this->assertSame(1, InventoryLog::where('product_id', $product->id)->where('type', 'order')->count());
        $this->assertSame(1, InventoryLog::where('product_id', $product->id)->where('type', 'return')->count());

        app(OrderInventoryService::class)->release($released->fresh());

        $this->assertSame(10, $product->fresh()->stock);
        $this->assertSame(1, InventoryLog::where('product_id', $product->id)->where('type', 'return')->count());
    }

    public function test_confirm_marks_reserved_order_paid_and_prevents_release(): void
    {
        [$order, $product] = $this->reservedProductOrder(quantity: 2, stock: 10);

        app(OrderInventoryService::class)->confirm($order->fresh());
        app(OrderInventoryService::class)->release($order->fresh());

        $confirmed = $order->fresh();
        $this->assertSame('paid', $confirmed->payment_status);
        $this->assertSame('processing', $confirmed->status);
        $this->assertNotNull($confirmed->inventory_reduced_at);
        $this->assertNull($confirmed->stock_released_at);
        $this->assertSame(8, $product->fresh()->stock);
    }

    public function test_release_variant_order_returns_variant_stock_not_product_stock(): void
    {
        $customer = $this->customer();
        $product = $this->product(price: 100000, stock: 20);
        $variant = $this->variant($product, price: 130000, stock: 6);

        $order = $this->order($customer);
        $order->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'product_name' => $product->name,
            'variant_name' => $variant->sku,
            'sku' => $variant->sku,
            'quantity' => 3,
            'unit_price' => 130000,
            'discount_price' => null,
            'total_price' => 390000,
        ]);

        app(OrderInventoryService::class)->reserve($order->fresh());
        $this->assertSame(20, $product->fresh()->stock);
        $this->assertSame(3, $variant->fresh()->stock);

        app(OrderInventoryService::class)->release($order->fresh());

        $this->assertSame(20, $product->fresh()->stock);
        $this->assertSame(6, $variant->fresh()->stock);
    }

    public function test_payment_start_is_forbidden_for_other_customers(): void
    {
        [$order] = $this->reservedProductOrder(quantity: 1, stock: 10);
        $other = $this->customer('09129999999');
        $payment = $this->pendingPayment($order, 'zarinpal');

        $this->withSession(['customer_id' => $other->id])
            ->get(route('frontend.payments.start', $payment))
            ->assertForbidden();
    }

    public function test_fake_success_marks_payment_and_order_paid_without_releasing_stock(): void
    {
        [$order, $product] = $this->reservedProductOrder(quantity: 2, stock: 10);
        $payment = $this->pendingPayment($order, 'zarinpal');

        $response = $this->withSession(['customer_id' => $order->customer_id])
            ->get(route('frontend.payments.fake.success', $payment));

        $response->assertRedirect(route('frontend.orders.thank-you', $order));
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame('processing', $order->fresh()->status);
        $this->assertNull($order->fresh()->stock_released_at);
        $this->assertSame(8, $product->fresh()->stock);
    }

    public function test_fake_fail_marks_payment_failed_cancels_order_and_releases_stock(): void
    {
        [$order, $product] = $this->reservedProductOrder(quantity: 2, stock: 10);
        $payment = $this->pendingPayment($order, 'zarinpal');

        $response = $this->withSession(['customer_id' => $order->customer_id])
            ->get(route('frontend.payments.fake.fail', $payment));

        $response->assertRedirect('/cart');
        $this->assertSame('failed', $payment->fresh()->status);
        $this->assertSame('failed', $order->fresh()->payment_status);
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->stock_released_at);
        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_paid_payment_cannot_be_failed_later(): void
    {
        [$order, $product] = $this->reservedProductOrder(quantity: 2, stock: 10);
        $payment = $this->pendingPayment($order, 'zarinpal');

        $this->withSession(['customer_id' => $order->customer_id])
            ->get(route('frontend.payments.fake.success', $payment));

        $this->withSession(['customer_id' => $order->customer_id])
            ->get(route('frontend.payments.fake.fail', $payment));

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('paid', $order->fresh()->payment_status);
        $this->assertSame('processing', $order->fresh()->status);
        $this->assertNull($order->fresh()->stock_released_at);
        $this->assertSame(8, $product->fresh()->stock);
    }

    public function test_failed_payment_cannot_be_paid_later(): void
    {
        [$order, $product] = $this->reservedProductOrder(quantity: 2, stock: 10);
        $payment = $this->pendingPayment($order, 'zarinpal');

        $this->withSession(['customer_id' => $order->customer_id])
            ->get(route('frontend.payments.fake.fail', $payment));

        $this->withSession(['customer_id' => $order->customer_id])
            ->get(route('frontend.payments.fake.success', $payment));

        $this->assertSame('failed', $payment->fresh()->status);
        $this->assertSame('failed', $order->fresh()->payment_status);
        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertNotNull($order->fresh()->stock_released_at);
        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_thank_you_page_is_forbidden_for_other_customers(): void
    {
        $customerA = $this->customer('09120000001');
        $customerB = $this->customer('09120000002');
        $province = $this->province();
        $city = $this->city($province);
        $shipping = $this->shippingMethod();
        $payment = $this->paymentMethod();
        $product = $this->product(price: 100000, stock: 10);
        $order = Order::create([
            'order_number' => 'MP-20260607-0001',
            'customer_id' => $customerA->id,
            'shipping_method_id' => $shipping->id,
            'payment_method_id' => $payment->id,
            'shipping_receiver_name' => 'Ali Ahmadi',
            'shipping_receiver_phone' => '09120000001',
            'shipping_province_name' => $province->name,
            'shipping_city_name' => $city->name,
            'shipping_address' => 'Tehran, Valiasr',
            'shipping_postal_code' => '1234567890',
            'shipping_method_name' => $shipping->name,
            'payment_method_name' => $payment->name,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'subtotal' => 100000,
            'discount_total' => 0,
            'shipping_cost' => 50000,
            'tax_total' => 0,
            'total' => 150000,
        ]);

        Cart::create([
            'customer_id' => $customerA->id,
            'status' => 'ordered',
        ]);

        $this->withSession(['customer_id' => $customerB->id])
            ->get(route('frontend.orders.thank-you', $order))
            ->assertForbidden();
    }

    private function customer(string $phone = '09120000000'): Customer
    {
        return Customer::create([
            'name' => 'Test Customer',
            'phone' => $phone,
            'email' => "{$phone}@example.com",
            'status' => 'active',
        ]);
    }

    private function product(float $price, int $stock): Product
    {
        return Product::create([
            'name' => 'Test Product',
            'slug' => 'test-product-' . fake()->unique()->randomNumber(6, true),
            'price' => $price,
            'discount_price' => null,
            'currency' => 'IRR',
            'status' => 'active',
            'type' => 'simple',
            'stock' => $stock,
        ]);
    }

    private function variant(Product $product, float $price, int $stock): ProductVariant
    {
        return ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'SKU-' . fake()->unique()->randomNumber(5, true),
            'price' => $price,
            'discount_price' => null,
            'stock' => $stock,
        ]);
    }

    private function province(): Province
    {
        return Province::create([
            'name' => 'Tehran',
            'slug' => 'tehran-' . fake()->unique()->randomNumber(4, true),
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function city(Province $province): City
    {
        return City::create([
            'province_id' => $province->id,
            'name' => 'Tehran City',
            'slug' => 'tehran-city-' . fake()->unique()->randomNumber(4, true),
            'is_active' => true,
            'sort_order' => 1,
        ]);
    }

    private function shippingMethod(float $baseCost = 50000, bool $active = true): ShippingMethod
    {
        return ShippingMethod::create([
            'name' => 'Standard Shipping',
            'slug' => 'standard-shipping-' . fake()->unique()->randomNumber(4, true),
            'type' => 'fixed',
            'base_cost' => $baseCost,
            'is_active' => $active,
            'sort_order' => 1,
        ]);
    }

    private function paymentMethod(bool $active = true, string $driver = 'manual'): PaymentMethod
    {
        return PaymentMethod::create([
            'name' => 'Online Payment',
            'slug' => $driver . '-payment-' . fake()->unique()->randomNumber(4, true),
            'driver' => $driver,
            'fee_type' => 'none',
            'fee_value' => 0,
            'is_active' => $active,
            'sort_order' => 1,
        ]);
    }

    private function checkoutFixture(string $paymentDriver = 'manual'): array
    {
        $customer = $this->customer(fake()->unique()->numerify('0912#######'));
        $province = $this->province();
        $city = $this->city($province);
        $shipping = $this->shippingMethod(baseCost: 50000);
        $payment = $this->paymentMethod(driver: $paymentDriver);
        $product = $this->product(price: 100000, stock: 10);

        $cart = Cart::create([
            'customer_id' => $customer->id,
            'status' => 'active',
        ]);

        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        return [$customer, $province, $city, $shipping, $payment, $product, $cart];
    }

    private function checkoutPayload(Province $province, City $city, ShippingMethod $shipping, PaymentMethod $payment): array
    {
        return [
            'recipient_name' => 'Ali Ahmadi',
            'recipient_mobile' => '09120000000',
            'province_id' => $province->id,
            'city_id' => $city->id,
            'address' => 'Tehran, Valiasr',
            'postal_code' => '1234567890',
            'shipping_method_id' => $shipping->id,
            'payment_method_id' => $payment->id,
            'note' => null,
        ];
    }

    private function reservedProductOrder(int $quantity, int $stock): array
    {
        $customer = $this->customer(fake()->unique()->numerify('0912#######'));
        $product = $this->product(price: 100000, stock: $stock);
        $order = $this->order($customer);

        $order->items()->create([
            'product_id' => $product->id,
            'product_variant_id' => null,
            'product_name' => $product->name,
            'variant_name' => null,
            'sku' => $product->sku,
            'quantity' => $quantity,
            'unit_price' => 100000,
            'discount_price' => null,
            'total_price' => 100000 * $quantity,
        ]);

        app(OrderInventoryService::class)->reserve($order->fresh());

        return [$order->fresh(), $product];
    }

    private function pendingPayment(Order $order, string $gateway = 'zarinpal'): Payment
    {
        return Payment::create([
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'amount' => $order->total,
            'method' => 'online',
            'gateway' => $gateway,
            'transaction_id' => 'PAY-' . $order->id . '-' . fake()->unique()->randomNumber(8, true),
            'status' => 'pending',
        ]);
    }

    private function order(Customer $customer): Order
    {
        $province = $this->province();
        $city = $this->city($province);
        $shipping = $this->shippingMethod();
        $payment = $this->paymentMethod();

        return Order::create([
            'order_number' => 'MP-' . fake()->unique()->randomNumber(8, true),
            'customer_id' => $customer->id,
            'shipping_method_id' => $shipping->id,
            'payment_method_id' => $payment->id,
            'shipping_receiver_name' => 'Ali Ahmadi',
            'shipping_receiver_phone' => $customer->phone,
            'shipping_province_name' => $province->name,
            'shipping_city_name' => $city->name,
            'shipping_address' => 'Tehran, Valiasr',
            'shipping_postal_code' => '1234567890',
            'shipping_method_name' => $shipping->name,
            'payment_method_name' => $payment->name,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'subtotal' => 100000,
            'discount_total' => 0,
            'shipping_cost' => 50000,
            'tax_total' => 0,
            'total' => 150000,
        ]);
    }
}
