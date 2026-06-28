<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProductReviewController extends Controller
{
    public function store(Request $request, Product $product): RedirectResponse
    {
        $customer = $this->customer($request);

        $review = ProductReview::query()
            ->where('product_id', $product->id)
            ->where('customer_id', $customer->id)
            ->first();

        if ($review?->status === 'approved') {
            throw ValidationException::withMessages([
                'comment' => 'نظر تایید شده قابل ویرایش نیست.',
            ]);
        }

        if ($review?->status === 'pending') {
            throw ValidationException::withMessages([
                'comment' => 'نظر شما در انتظار تایید است. برای تغییر از ویرایش نظر استفاده کنید.',
            ]);
        }

        $data = $this->validated($request);

        ProductReview::query()->updateOrCreate(
            [
                'product_id' => $product->id,
                'customer_id' => $customer->id,
            ],
            [
                ...$data,
                'status' => 'pending',
                'is_buyer' => $this->isBuyer($customer, $product),
                'approved_at' => null,
                'rejected_at' => null,
            ]
        );

        return back()->with('success', 'نظر شما ثبت شد و بعد از تایید نمایش داده می‌شود.');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $customer = $this->customer($request);

        $review = ProductReview::query()
            ->where('product_id', $product->id)
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        if ($review->status === 'approved') {
            throw ValidationException::withMessages([
                'comment' => 'نظر تایید شده قابل ویرایش نیست.',
            ]);
        }

        $review->update([
            ...$this->validated($request),
            'status' => 'pending',
            'is_buyer' => $this->isBuyer($customer, $product),
            'approved_at' => null,
            'rejected_at' => null,
        ]);

        return back()->with('success', 'نظر شما برای بررسی دوباره ارسال شد.');
    }

    private function customer(Request $request): Customer
    {
        $customerId = $request->session()->get('customer_id');

        if (! $customerId) {
            abort(403);
        }

        return Customer::query()->findOrFail($customerId);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'title' => ['nullable', 'string', 'max:255'],
            'comment' => ['required', 'string', 'min:3', 'max:3000'],
        ]);
    }

    private function isBuyer(Customer $customer, Product $product): bool
    {
        return Order::query()
            ->where('customer_id', $customer->id)
            ->where('payment_status', 'paid')
            ->whereIn('status', ['processing', 'shipped', 'delivered'])
            ->whereHas('items', fn ($query) => $query->where('product_id', $product->id))
            ->exists();
    }
}
