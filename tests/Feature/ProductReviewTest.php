<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ProductReviewTest extends TestCase
{
    use DatabaseTransactions;

    public function test_guest_cannot_submit_product_review(): void
    {
        $product = $this->product();

        $this->post("/products/{$product->slug}/reviews", $this->reviewPayload())
            ->assertForbidden();

        $this->assertSame(0, ProductReview::where('product_id', $product->id)->count());
    }

    public function test_customer_can_submit_new_review(): void
    {
        $customer = $this->customer();
        $product = $this->product();

        $this->withSession(['customer_id' => $customer->id])
            ->post("/products/{$product->slug}/reviews", $this->reviewPayload())
            ->assertRedirect();

        $review = ProductReview::where('product_id', $product->id)->where('customer_id', $customer->id)->firstOrFail();

        $this->assertSame($customer->id, $review->customer_id);
        $this->assertSame($product->id, $review->product_id);
        $this->assertSame('pending', $review->status);
        $this->assertFalse($review->is_buyer);
    }

    public function test_customer_cannot_create_second_review_for_same_product(): void
    {
        $customer = $this->customer();
        $product = $this->product();

        ProductReview::create([
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'rating' => 4,
            'title' => 'First',
            'comment' => 'First comment',
            'status' => 'pending',
        ]);

        $this->withSession(['customer_id' => $customer->id])
            ->post("/products/{$product->slug}/reviews", $this->reviewPayload(['title' => 'Second']))
            ->assertSessionHasErrors('comment');

        $this->assertSame(1, ProductReview::where('customer_id', $customer->id)->where('product_id', $product->id)->count());
    }

    public function test_pending_review_can_be_updated(): void
    {
        $customer = $this->customer();
        $product = $this->product();
        $review = $this->review($customer, $product, ['status' => 'pending']);

        $this->withSession(['customer_id' => $customer->id])
            ->patch("/products/{$product->slug}/reviews", $this->reviewPayload(['rating' => 2, 'title' => 'Updated']))
            ->assertRedirect();

        $review->refresh();

        $this->assertSame(2, $review->rating);
        $this->assertSame('Updated', $review->title);
        $this->assertSame('pending', $review->status);
    }

    public function test_rejected_review_can_be_updated_and_resubmitted(): void
    {
        $customer = $this->customer();
        $product = $this->product();
        $review = $this->review($customer, $product, [
            'status' => 'rejected',
            'rejected_at' => now(),
        ]);

        $this->withSession(['customer_id' => $customer->id])
            ->patch("/products/{$product->slug}/reviews", $this->reviewPayload(['comment' => 'Fixed comment']))
            ->assertRedirect();

        $review->refresh();

        $this->assertSame('pending', $review->status);
        $this->assertNull($review->rejected_at);
        $this->assertSame('Fixed comment', $review->comment);
    }

    public function test_approved_review_cannot_be_updated(): void
    {
        $customer = $this->customer();
        $product = $this->product();
        $review = $this->review($customer, $product, [
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        $this->withSession(['customer_id' => $customer->id])
            ->patch("/products/{$product->slug}/reviews", $this->reviewPayload(['title' => 'Changed']))
            ->assertSessionHasErrors('comment');

        $this->assertNotSame('Changed', $review->fresh()->title);
    }

    public function test_buyer_detection_marks_review_as_buyer(): void
    {
        $customer = $this->customer();
        $product = $this->product();

        $order = Order::create([
            'order_number' => 'MP-' . uniqid(),
            'customer_id' => $customer->id,
            'status' => 'processing',
            'payment_status' => 'paid',
            'subtotal' => 100000,
            'total' => 100000,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'quantity' => 1,
            'unit_price' => 100000,
            'total_price' => 100000,
        ]);

        $this->withSession(['customer_id' => $customer->id])
            ->post("/products/{$product->slug}/reviews", $this->reviewPayload())
            ->assertRedirect();

        $this->assertTrue(ProductReview::where('product_id', $product->id)->where('customer_id', $customer->id)->firstOrFail()->is_buyer);
    }

    public function test_only_approved_reviews_are_rendered_on_product_page(): void
    {
        $customer = $this->customer();
        $product = $this->product();

        $this->review($customer, $product, [
            'title' => 'Visible review',
            'comment' => 'Approved text',
            'status' => 'approved',
            'approved_at' => now(),
        ]);

        $this->review($this->customer('09120000002'), $product, [
            'title' => 'Hidden review',
            'comment' => 'Pending text',
            'status' => 'pending',
        ]);

        $response = $this->get("/products/{$product->slug}");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Frontend/Products/Show', false)
            ->has('reviews', 1)
            ->where('reviews.0.title', 'Visible review')
            ->where('reviewSummary.count', 1)
        );
    }

    public function test_admin_can_approve_and_reject_reviews(): void
    {
        $review = $this->review($this->customer(), $this->product(), ['status' => 'pending']);
        $admin = User::factory()->create();

        $this->actingAs($admin)
            ->patch("/admin/product-reviews/{$review->id}/approve")
            ->assertRedirect();

        $this->assertSame('approved', $review->fresh()->status);
        $this->assertNotNull($review->fresh()->approved_at);

        $this->actingAs($admin)
            ->patch("/admin/product-reviews/{$review->id}/reject")
            ->assertRedirect();

        $this->assertSame('rejected', $review->fresh()->status);
        $this->assertNotNull($review->fresh()->rejected_at);
    }

    private function customer(string $phone = '09120000001'): Customer
    {
        return Customer::create([
            'name' => 'Test Customer',
            'phone' => $phone,
            'status' => 'active',
        ]);
    }

    private function product(): Product
    {
        return Product::create([
            'name' => 'Test Product',
            'slug' => 'test-product-' . uniqid(),
            'price' => 100000,
            'status' => 'active',
            'type' => 'simple',
            'stock' => 10,
        ]);
    }

    private function review(Customer $customer, Product $product, array $overrides = []): ProductReview
    {
        return ProductReview::create([
            'customer_id' => $customer->id,
            'product_id' => $product->id,
            'rating' => 4,
            'title' => 'Good',
            'comment' => 'Good product',
            'status' => 'pending',
            ...$overrides,
        ]);
    }

    private function reviewPayload(array $overrides = []): array
    {
        return [
            'rating' => 5,
            'title' => 'Great',
            'comment' => 'Great product quality',
            ...$overrides,
        ];
    }
}
