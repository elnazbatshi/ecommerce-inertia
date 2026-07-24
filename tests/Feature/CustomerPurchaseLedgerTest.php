<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerPurchaseLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_customer_purchase_ledger(): void
    {
        $this->get('/admin/customer-purchase-ledger')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_open_without_customer(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/customer-purchase-ledger')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('hasSelectedCustomer', false)
                ->where('statistics.total_spent', 0)
                ->missing('orderRows.data')
            );
    }

    public function test_valid_customer_can_be_selected(): void
    {
        $customer = $this->customer(['name' => 'Ledger Customer']);

        $this->actingAs(User::factory()->create())
            ->get("/admin/customer-purchase-ledger?customer_id={$customer->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('hasSelectedCustomer', true)
                ->where('selectedCustomer.id', $customer->id)
                ->where('selectedCustomer.name', 'Ledger Customer')
            );
    }

    public function test_only_final_paid_orders_are_reported(): void
    {
        $customer = $this->customer();
        $visible = $this->order($customer, ['order_number' => 'ORD-VISIBLE']);
        $this->item($visible, ['product_name' => 'Visible Product']);
        $this->payment($visible, ['transaction_id' => 'VISIBLE-TX']);

        foreach ([
            ['payment_status' => 'unpaid', 'paid_at' => null, 'order_number' => 'ORD-UNPAID'],
            ['payment_status' => 'paid', 'paid_at' => null, 'order_number' => 'ORD-NO-PAID-AT'],
            ['status' => 'cancelled', 'order_number' => 'ORD-CANCELLED'],
            ['status' => 'returned', 'order_number' => 'ORD-RETURNED'],
        ] as $attributes) {
            $order = $this->order($customer, $attributes);
            $this->item($order, ['product_name' => $attributes['order_number']]);
        }

        $this->actingAs(User::factory()->create())
            ->get("/admin/customer-purchase-ledger?customer_id={$customer->id}")
            ->assertOk()
            ->assertSee('ORD-VISIBLE')
            ->assertDontSee('ORD-UNPAID')
            ->assertDontSee('ORD-NO-PAID-AT')
            ->assertDontSee('ORD-CANCELLED')
            ->assertDontSee('ORD-RETURNED');
    }

    public function test_statistics_are_calculated_from_order_level_without_duplicate_payments_or_items(): void
    {
        $customer = $this->customer();
        $order = $this->order($customer, [
            'subtotal' => 300000,
            'discount_total' => 20000,
            'shipping_cost' => 15000,
            'total' => 295000,
            'paid_at' => now(),
        ]);
        $this->item($order, ['product_name' => 'First', 'quantity' => 2, 'total_price' => 180000]);
        $this->item($order, ['product_name' => 'Second', 'quantity' => 1, 'total_price' => 100000]);
        $this->payment($order, ['transaction_id' => 'OLD-TX', 'paid_at' => now()->subMinute()]);
        $this->payment($order, ['transaction_id' => 'NEW-TX', 'paid_at' => now()]);

        $this->actingAs(User::factory()->create())
            ->get("/admin/customer-purchase-ledger?customer_id={$customer->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('statistics.total_spent', 295000)
                ->where('statistics.orders_count', 1)
                ->where('statistics.items_count', 3)
                ->where('statistics.discount_total', 20000)
                ->where('statistics.shipping_total', 15000)
                ->where('statistics.average_order_value', 295000)
                ->where('orderRows.data.0.transaction_id', 'NEW-TX')
            );
    }

    public function test_products_tab_groups_customer_products_and_keeps_deleted_product_snapshot(): void
    {
        $customer = $this->customer();
        $order = $this->order($customer);
        $item = $this->item($order, ['product_name' => 'Snapshot Product', 'sku' => 'SNAP-SKU', 'quantity' => 2, 'total_price' => 200000]);
        $this->item($order, ['product_id' => $item->product_id, 'product_name' => 'Snapshot Product', 'sku' => 'SNAP-SKU', 'quantity' => 1, 'total_price' => 100000]);
        $item->product->delete();

        $this->actingAs(User::factory()->create())
            ->get("/admin/customer-purchase-ledger?customer_id={$customer->id}&active_view=products")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('purchasedProductRows.data.0.product_name', 'Snapshot Product')
                ->where('purchasedProductRows.data.0.product_sku', 'SNAP-SKU')
                ->where('purchasedProductRows.data.0.quantity_purchased', 3)
                ->where('purchasedProductRows.data.0.net_amount', 300000)
                ->where('purchasedProductRows.data.0.is_deleted', true)
            );
    }

    public function test_payments_tab_shows_only_latest_successful_payment_per_order(): void
    {
        $customer = $this->customer();
        $order = $this->order($customer);
        $this->item($order);
        $this->payment($order, ['transaction_id' => 'OLD-TX', 'paid_at' => now()->subMinute()]);
        $this->payment($order, ['transaction_id' => 'NEW-TX', 'paid_at' => now()]);
        $this->payment($order, ['transaction_id' => 'FAILED-TX', 'status' => 'failed', 'paid_at' => null]);

        $this->actingAs(User::factory()->create())
            ->get("/admin/customer-purchase-ledger?customer_id={$customer->id}&active_view=payments")
            ->assertOk()
            ->assertSee('NEW-TX')
            ->assertDontSee('OLD-TX')
            ->assertDontSee('FAILED-TX');
    }

    public function test_tabs_only_return_their_own_heavy_rows(): void
    {
        $customer = $this->customer();
        $order = $this->order($customer);
        $this->item($order);

        $this->actingAs(User::factory()->create())
            ->get("/admin/customer-purchase-ledger?customer_id={$customer->id}&active_view=products")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('purchasedProductRows.data')
                ->missing('orderRows.data')
                ->missing('paymentRows.data')
            );

        $this->actingAs(User::factory()->create())
            ->get("/admin/customer-purchase-ledger?customer_id={$customer->id}&active_view=analytics")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('analyticsData.purchase_amount_trend')
                ->missing('orderRows.data')
                ->missing('purchasedProductRows.data')
            );
    }

    public function test_invalid_customer_id_fails_validation(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/customer-purchase-ledger?customer_id=999999')
            ->assertSessionHasErrors('customer_id');
    }

    public function test_variant_items_are_preserved_in_order_expansion(): void
    {
        $customer = $this->customer();
        $order = $this->order($customer);
        $product = $this->product();
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'sku' => 'VAR-SKU',
            'price' => 120000,
            'stock' => 5,
        ]);

        $this->item($order, [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'product_name' => 'Variant Product Snapshot',
            'variant_name' => 'Red Large',
            'sku' => 'VAR-SKU',
        ]);

        $this->actingAs(User::factory()->create())
            ->get("/admin/customer-purchase-ledger?customer_id={$customer->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('orderRows.data.0.items.0.variant_name', 'Red Large')
                ->where('orderRows.data.0.items.0.sku', 'VAR-SKU')
            );
    }

    public function test_lifetime_value_ignores_date_filter_while_total_spent_respects_it(): void
    {
        $customer = $this->customer();
        $old = $this->order($customer, ['total' => 100000, 'paid_at' => now()->subDays(40)]);
        $new = $this->order($customer, ['total' => 200000, 'paid_at' => now()]);
        $this->item($old);
        $this->item($new);

        $this->actingAs(User::factory()->create())
            ->get("/admin/customer-purchase-ledger?customer_id={$customer->id}&date_from=".now()->toDateString().'&date_to='.now()->toDateString())
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('statistics.total_spent', 200000)
                ->where('lifetimeValue', 300000)
            );
    }

    public function test_last_purchase_and_purchase_frequency_are_calculated_from_final_orders(): void
    {
        $customer = $this->customer();
        $first = $this->order($customer, ['order_number' => 'ORD-FIRST', 'paid_at' => now()->subDays(20)]);
        $last = $this->order($customer, ['order_number' => 'ORD-LAST', 'paid_at' => now()->subDays(5)]);
        $unpaid = $this->order($customer, ['order_number' => 'ORD-UNPAID', 'payment_status' => 'unpaid', 'paid_at' => null]);
        $this->item($first);
        $this->item($last, ['product_name' => 'Last Product']);
        $this->item($unpaid);

        $this->actingAs(User::factory()->create())
            ->get("/admin/customer-purchase-ledger?customer_id={$customer->id}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('lastPurchase.order_number', 'ORD-LAST')
                ->where('lastPurchase.first_product_name', 'Last Product')
                ->where('purchaseFrequency.average_interval_days', 15)
                ->where('purchaseFrequency.repeat_purchase_count', 1)
            );
    }

    public function test_analytics_returns_repurchase_suggestions_and_timeline_is_paginated(): void
    {
        $customer = $this->customer();
        $product = $this->product(['name' => 'Repeat Oil', 'status' => 'active']);

        foreach ([120, 60, 5] as $daysAgo) {
            $order = $this->order($customer, ['paid_at' => now()->subDays($daysAgo)]);
            $this->item($order, ['product_id' => $product->id, 'product_name' => 'Repeat Oil', 'sku' => 'OIL-SKU']);
        }

        $this->actingAs(User::factory()->create())
            ->get("/admin/customer-purchase-ledger?customer_id={$customer->id}&active_view=analytics")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('frequentlyPurchasedProducts.0')
                ->has('reminderProducts.0')
                ->has('nextPurchaseSuggestions')
                ->missing('orderRows.data')
            );

        $this->actingAs(User::factory()->create())
            ->get("/admin/customer-purchase-ledger?customer_id={$customer->id}&active_view=timeline&timeline_per_page=10")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('timelineRows.data', 3)
                ->missing('purchasedProductRows.data')
            );
    }

    private function customer(array $attributes = []): Customer
    {
        return Customer::create([
            'name' => 'Test Customer',
            'phone' => fake()->unique()->numerify('0912#######'),
            'email' => fake()->unique()->safeEmail(),
            'status' => 'active',
            ...$attributes,
        ]);
    }

    private function order(Customer $customer, array $attributes = []): Order
    {
        return Order::create([
            'order_number' => fake()->unique()->bothify('ORD-####'),
            'customer_id' => $customer->id,
            'status' => 'processing',
            'payment_status' => 'paid',
            'subtotal' => 100000,
            'discount_total' => 0,
            'shipping_cost' => 0,
            'tax_total' => 0,
            'total' => 100000,
            'paid_at' => now(),
            ...$attributes,
        ]);
    }

    private function product(array $attributes = []): Product
    {
        return Product::create([
            'name' => fake()->unique()->words(3, true),
            'slug' => fake()->unique()->slug(),
            'sku' => fake()->unique()->bothify('SKU-####'),
            'price' => 100000,
            'currency' => 'IRR',
            'status' => 'active',
            'type' => 'simple',
            'stock' => 10,
            ...$attributes,
        ]);
    }

    private function item(Order $order, array $attributes = []): OrderItem
    {
        $product = $attributes['product_id'] ?? null ? Product::find($attributes['product_id']) : $this->product();

        return OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product?->id,
            'product_name' => 'Snapshot Product',
            'sku' => 'SNAP-SKU',
            'quantity' => 1,
            'unit_price' => 100000,
            'discount_price' => null,
            'total_price' => 100000,
            ...$attributes,
        ]);
    }

    private function payment(Order $order, array $attributes = []): Payment
    {
        return Payment::create([
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'amount' => $order->total,
            'method' => 'online',
            'gateway' => 'fake',
            'transaction_id' => fake()->unique()->bothify('TX-####'),
            'status' => 'paid',
            'paid_at' => $order->paid_at,
            ...$attributes,
        ]);
    }
}
