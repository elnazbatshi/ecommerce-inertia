<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class VehicleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vehicle_brand_id' => $this->vehicle_brand_id,
            'type' => $this->type,
            'name' => $this->name,
            'slug' => $this->slug,
            'year_from' => $this->year_from,
            'year_to' => $this->year_to,
            'engine' => $this->engine,
            'trim' => $this->trim,
            'description' => $this->description,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'products_count' => $this->whenCounted('products'),
            'image_media_id' => $this->image_media_id,
            'brand' => $this->whenLoaded('brand', fn () => [
                'id' => $this->brand?->id,
                'name' => $this->brand?->name,
                'slug' => $this->brand?->slug,
                'type' => $this->brand?->type,
            ]),
            'image_media' => $this->whenLoaded('imageMedia', fn () => [
                'id' => $this->imageMedia?->id,
                'original_name' => $this->imageMedia?->original_name,
                'url' => $this->imageMedia?->path ? Storage::disk('public')->url($this->imageMedia->path) : null,
            ]),
        ];
    }
}

