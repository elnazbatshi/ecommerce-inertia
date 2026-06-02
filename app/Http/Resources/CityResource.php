<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'province_id' => $this->province_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'code' => $this->code,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'province' => $this->whenLoaded('province', fn () => [
                'id' => $this->province?->id,
                'name' => $this->province?->name,
                'slug' => $this->province?->slug,
            ]),
        ];
    }
}

