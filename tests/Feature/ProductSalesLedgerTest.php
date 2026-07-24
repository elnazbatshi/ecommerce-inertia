<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSalesLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_product_sales_ledger(): void
    {
        $this->get('/admin/product-sales-ledger')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_open_product_sales_ledger(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/product-sales-ledger')
            ->assertOk();
    }

    public function test_paid_order_with_paid_at_is_visible(): void
    {
        $item = $this->ledgerItem();

        $this->actingAs(User::factory()->create())
            ->get('/admin/product-sales-ledger')
            ->assertOk()
            ->assertSee($item->product_name);
    }

    public function test_unpaid_order_is_not_visible(): void
    {
        $item = $this->ledgerItem(order: ['payment_status' => 'unpaid', 'paid_at' => null]);

        $this->actingAs(User::factory()->create())
            ->get('/admin/product-sales-ledger')
            ->assertOk()
            ->assertDontSee($item->product_name);
    }

    public function test_paid_order_without_paid_at_is_not_visible(): void
    {
        $item = $this->ledgerItem(order: ['payment_status' => 'paid', 'paid_at' => null]);

        $this->actingAs(User::factory()->create())
            ->get('/admin/product-sales-ledger')
            ->assertOk()
            ->assertDontSee($item->product_name);
    }

    public function test_cancelled_and_returned_orders_are_not_visible(): void
    {
        $cancelled = $this->ledgerItem(order: ['status' => 'cancelled']);
        $returned = $this->ledgerItem(order: ['status' => 'returned']);

        $this->actingAs(User::factory()->create())
            ->get('/admin/product-sales-ledger')
            ->assertOk()
            ->assertDontSee($cancelled->product_name)
            ->assertDontSee($returned->product_name);
    }

    public function test_each_order_item_is_a_separate_row_and_payments_do_not_duplicate_rows(): void
    {
        $order = $this->order();
        $this->item($order, ['product_name' => 'Snapshot A', 'sku' => 'SKU-A']);
        $this->item($order, ['product_name' => 'Snapshot B', 'sku' => 'SKU-B']);
        $this->payment($order, ['transaction_id' => 'OLD-TX', 'paid_at' => now()->subMinute()]);
        $this->payment($order, ['transaction_id' => 'NEW-TX', 'paid_at' => now()]);

        $this->actingAs(User::factory()->create())
            ->get('/admin/product-sales-ledger')
            ->assertOk()
            ->assertSee('Snapshot A')
            ->assertSee('Snapshot B')
            ->assertSee('NEW-TX')
            ->assertDontSee('OLD-TX');
    }

    public function test_snapshot_name_and_sku_are_used_even_if_product_changes(): void
    {
        $item = $this->ledgerItem(['product_name' => 'Old Snapshot Name', 'sku' => 'OLD-SKU']);
        $item->product->update(['name' => 'New Product Name', 'sku' => 'NEW-SKU']);

        $this->actingAs(User::factory()->create())
            ->get('/admin/product-sales-ledger')
            ->assertOk()
            ->assertSee('Old Snapshot Name')
            ->assertSee('OLD-SKU')
            ->assertDontSee('New Product Name')
            ->assertDontSee('NEW-SKU');
    }

    public function test_filters_work_for_order_number_transaction_search_and_date(): void
    {
        $visible = $this->ledgerItem(['product_name' => 'Brake Pad Snapshot'], ['order_number' => 'ORD-YES', 'paid_at' => now()]);
        $this->payment($visible->order, ['transaction_id' => 'TX-YES', 'paid_at' => now()]);
        $hidden = $this->ledgerItem(['product_name' => 'Oil Snapshot'], ['order_number' => 'ORD-NO', 'paid_at' => now()->subDays(5)]);
        $this->payment($hidden->order, ['transaction_id' => 'TX-NO', 'paid_at' => now()->subDays(5)]);

        $this->actingAs(User::factory()->create())
            ->get('/admin/product-sales-ledger?search=Brake&order_number=ORD-YES&transaction_id=TX-YES&date_from='.now()->toDateString().'&date_to='.now()->toDateString())
            ->assertOk()
            ->assertSee('Brake Pad Snapshot')
            ->assertDontSee('Oil Snapshot');
    }

    public function test_invalid_date_range_fails_validation(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/admin/product-sales-ledger?date_from=2026-05-02&date_to=2026-05-01')
            ->assertSessionHasErrors('date_to');
    }

    public function test_products_and_chart_tabs_do_not_load_rows(): void
    {
        $this->ledgerItem();

        $this->actingAs(User::factory()->create())
            ->get('/admin/product-sales-ledger?active_view=products')
            ->assertOk()
            ->assertDontSee('Snapshot Product');

        $this->actingAs(User::factory()->create())
            ->get('/admin/product-sales-ledger?active_view=chart')
            ->assertOk()
            ->assertDontSee('Snapshot Product');
    }

    public function test_statistics_calculate_only_final_sales(): void
    {
        $this->ledgerItem(['quantity' => 2, 'unit_price' => 100000, 'discount_price' => 80000, 'total_price' => 160000]);
        $this->ledgerItem(['quantity' => 1, 'unit_price' => 50000, 'discount_price' => null, 'total_price' => 50000], ['payment_status' => 'unpaid', 'paid_at' => null]);

        $this->actingAs(User::factory()->create())
            ->get('/admin/product-sales-ledger')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('statistics.total_sales', 160000)
                ->where('statistics.orders_count', 1)
                ->where('statistics.items_sold', 2)
                ->where('statistics.discount_total', 40000)
                ->where('statistics.average_order_value', 160000)
            );
    }

    public function test_product_summary_groups_items_and_keeps_deleted_product_snapshot(): void
    {
        $first = $this->ledgerItem(['product_name' => 'Snapshot Group', 'sku' => 'GROUP-SKU', 'quantity' => 2, 'total_price' => 200000]);
        $this->item($first->order, [
            'product_id' => $first->product_id,
            'product_name' => 'Snapshot Group',
            'sku' => 'GROUP-SKU',
            'quantity' => 1,
            'total_price' => 100000,
        ]);
        $first->product->delete();

        $this->actingAs(User::factory()->create())
            ->get('/admin/product-sales-ledger?active_view=products')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('productSummaryRows.data.0.product_name', 'Snapshot Group')
                ->where('productSummaryRows.data.0.product_sku', 'GROUP-SKU')
                ->where('productSummaryRows.data.0.quantity_sold', 3)
                ->where('productSummaryRows.data.0.net_sales', 300000)
                ->where('productSummaryRows.data.0.product_deleted', true)
            );
    }

    public function test_chart_tab_returns_chart_data_without_transaction_rows(): void
    {
        $this->ledgerItem();

        $this->actingAs(User::factory()->create())
            ->get('/admin/product-sales-ledger?active_view=chart&chart_grouping=day')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('chartData.timeline')
                ->has('chartData.top_products')
                ->has('chartData.payment_methods')
                ->missing('transactionRows.data')
                ->missing('productSummaryRows.data')
            );
    }

    private function ledgerItem(array $item = [], array $order = []): OrderItem
    {
        return $this->item($this->order($order), $item);
    }

    private function order(array $attributes = []): Order
    {
        $customer = Customer::create([
            'name' => 'Test Customer',
            'phone' => fake()->unique()->numerify('0912#######'),
            'status' => 'active',
        ]);

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

    private function item(Order $order, array $attributes = []): OrderItem
    {
        $product = Product::create([
            'name' => 'Live Product',
            'slug' => fake()->unique()->slug(),
            'sku' => fake()->unique()->bothify('SKU-####'),
            'price' => 100000,
            'currency' => 'IRR',
            'status' => 'active',
            'type' => 'simple',
            'stock' => 10,
        ]);

        return OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
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
