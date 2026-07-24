<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerPurchasedProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $productExists = $this->product_id !== null && $this->product_deleted_at === null;

        return [
            'group_key' => $this->group_key,
            'product_id' => $this->product_id,
            'product_exists' => $productExists,
            'product_name' => $this->product_name,
            'product_sku' => $this->product_sku ?: null,
            'image_url' => $this->product_image,
            'quantity_purchased' => (int) $this->quantity_purchased,
            'orders_count' => (int) $this->orders_count,
            'gross_amount' => (float) $this->gross_amount,
            'discount_amount' => (float) $this->discount_amount,
            'net_amount' => (float) $this->net_amount,
            'average_unit_price' => (float) $this->average_unit_price,
            'first_purchased_at' => $this->first_purchased_at,
            'last_purchased_at' => $this->last_purchased_at,
            'product_url' => $productExists && $this->product_slug ? route('admin.products.show', $this->product_slug, false) : null,
            'is_deleted' => ! $productExists,
            'variant_summary' => $this->variant_summary ?? [],
        ];
    }
}
