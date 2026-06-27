<?php

namespace App\Http\Controllers;

use App\Models\ProductReview;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductReviewController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->query('status');

        $reviews = ProductReview::query()
            ->with(['product:id,name,slug', 'customer:id,name,phone'])
            ->when(in_array($status, ProductReview::STATUSES, true), fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate((int) $request->query('rows', 10))
            ->withQueryString()
            ->through(fn (ProductReview $review) => [
                'id' => $review->id,
                'rating' => $review->rating,
                'title' => $review->title,
                'comment' => $review->comment,
                'status' => $review->status,
                'is_buyer' => $review->is_buyer,
                'approved_at' => $review->approved_at?->toDateTimeString(),
                'rejected_at' => $review->rejected_at?->toDateTimeString(),
                'created_at' => $review->created_at?->toDateTimeString(),
                'product' => $review->product ? [
                    'id' => $review->product->id,
                    'name' => $review->product->name,
                    'slug' => $review->product->slug,
                ] : null,
                'customer' => $review->customer ? [
                    'id' => $review->customer->id,
                    'name' => $review->customer->name,
                    'phone' => $review->customer->phone,
                ] : null,
            ]);

        return Inertia::render('ProductReviews/Index', [
            'reviews' => $reviews,
            'filters' => $request->only(['status', 'rows']),
            'statusOptions' => $this->statusOptions(),
        ]);
    }

    public function approve(ProductReview $productReview): RedirectResponse
    {
        $productReview->update([
            'status' => 'approved',
            'approved_at' => now(),
            'rejected_at' => null,
        ]);

        return back()->with('success', 'نظر تایید شد.');
    }

    public function reject(ProductReview $productReview): RedirectResponse
    {
        $productReview->update([
            'status' => 'rejected',
            'approved_at' => null,
            'rejected_at' => now(),
        ]);

        return back()->with('success', 'نظر رد شد.');
    }

    public function destroy(ProductReview $productReview): RedirectResponse
    {
        $productReview->delete();

        return back()->with('success', 'نظر حذف شد.');
    }

    private function statusOptions(): array
    {
        return [
            ['label' => 'در انتظار تایید', 'value' => 'pending', 'severity' => 'warn'],
            ['label' => 'تایید شده', 'value' => 'approved', 'severity' => 'success'],
            ['label' => 'رد شده', 'value' => 'rejected', 'severity' => 'danger'],
        ];
    }
}
