<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductSalesSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'product_slug' => $this->product_slug,
            'product_image' => $this->product_image,
            'product_deleted' => $this->product_id === null || $this->product_deleted_at !== null,
            'product_name' => $this->product_name,
            'product_sku' => $this->product_sku ?: null,
            'quantity_sold' => (int) $this->quantity_sold,
            'orders_count' => (int) $this->orders_count,
            'gross_sales' => (float) $this->gross_sales,
            'discount_total' => (float) $this->discount_total,
            'net_sales' => (float) $this->net_sales,
            'average_unit_price' => (float) $this->average_unit_price,
            'last_sold_at' => $this->last_sold_at,
            'sales_share' => round((float) $this->sales_share, 2),
        ];
    }
}
