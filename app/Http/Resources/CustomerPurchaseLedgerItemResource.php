<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerPurchaseLedgerItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'created_at' => $this->created_at?->toDateTimeString(),
            'paid_at' => $this->paid_at?->toDateTimeString(),
            'items_count' => (int) ($this->items_count ?? 0),
            'subtotal' => (float) $this->subtotal,
            'discount_amount' => (float) $this->discount_total,
            'shipping_amount' => (float) $this->shipping_cost,
            'total_amount' => (float) $this->total,
            'status' => $this->status,
            'status_label' => $this->status,
            'payment_method' => $this->latest_payment_method ?: $this->payment_method_name,
            'transaction_id' => $this->latest_payment_transaction_id ?: $this->latest_payment_reference_id,
            'order_url' => route('admin.orders.show', $this->id, false),
            'items' => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'product_exists' => $item->product !== null && $item->product?->deleted_at === null,
                'product_name' => $item->product_name,
                'variant_name' => $item->variant_name,
                'sku' => $item->sku,
                'quantity' => (int) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'discount_amount' => $item->discount_price !== null ? (float) $item->discount_price : null,
                'total_amount' => (float) $item->total_price,
                'image_url' => $item->product?->main_image,
                'product_url' => $item->product && ! $item->product->deleted_at ? route('admin.products.show', $item->product, false) : null,
                'is_deleted' => $item->product === null || $item->product?->deleted_at !== null,
            ])->values(),
        ];
    }
}
