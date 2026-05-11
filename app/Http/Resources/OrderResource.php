<?php

namespace App\Http\Resources;

use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'customer' => $this->whenLoaded('customer'),
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'total' => $this->total,
            'items_count' => $this->items_count ?? 0,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];

        if ($this->relationLoaded('items')) {
            $data += [
                'customer_id' => $this->customer_id,
                'address_id' => $this->address_id,
                'address' => $this->whenLoaded('address'),
                'subtotal' => $this->subtotal,
                'discount_total' => $this->discount_total,
                'shipping_cost' => $this->shipping_cost,
                'tax_total' => $this->tax_total,
                'customer_note' => $this->customer_note,
                'admin_note' => $this->admin_note,
                'paid_at' => $this->paid_at?->toDateTimeString(),
                'shipped_at' => $this->shipped_at?->toDateTimeString(),
                'delivered_at' => $this->delivered_at?->toDateTimeString(),
                'cancelled_at' => $this->cancelled_at?->toDateTimeString(),
                'items' => $this->items->map(fn (OrderItem $item) => [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'product_name' => $item->product_name,
                    'variant_name' => $item->variant_name,
                    'sku' => $item->sku,
                    'quantity' => $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'discount_price' => $item->discount_price !== null ? (float) $item->discount_price : null,
                    'total_price' => (float) $item->total_price,
                ])->values(),
            ];
        }

        return $data;
    }
}
