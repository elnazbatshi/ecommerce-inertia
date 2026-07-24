<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductSalesLedgerItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $order = $this->order;
        $product = $this->product;

        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'order_number' => $order?->order_number,
            'product_id' => $this->product_id,
            'product_slug' => $product?->slug,
            'product_image' => $product?->main_image,
            'product_deleted' => $product === null || $product?->deleted_at !== null,
            'product_name' => $this->product_name,
            'product_sku' => $this->sku,
            'variant_name' => $this->variant_name,
            'quantity' => (int) $this->quantity,
            'unit_price' => (float) $this->unit_price,
            'discount_amount' => $this->discount_price !== null ? (float) $this->discount_price : null,
            'total_amount' => (float) $this->total_price,
            'customer_name' => $order?->customer?->name,
            'customer_phone' => $order?->customer?->phone,
            'payment_method' => $order?->payment_method_name ?: $this->latest_payment_method,
            'transaction_id' => $this->latest_payment_transaction_id ?: $this->latest_payment_reference_id,
            'paid_at' => $this->latest_payment_paid_at ?: $order?->paid_at?->toDateTimeString(),
            'order_status' => $order?->status,
            'order_show_url' => $order ? route('admin.orders.show', $order, false) : null,
        ];
    }
}
