<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShippingMethodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'type' => $this->type,
            'base_cost' => $this->base_cost,
            'free_from_amount' => $this->free_from_amount,
            'min_order_amount' => $this->min_order_amount,
            'max_order_amount' => $this->max_order_amount,
            'estimated_delivery_days' => $this->estimated_delivery_days,
            'settings' => $this->settings ?? [],
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }
}

