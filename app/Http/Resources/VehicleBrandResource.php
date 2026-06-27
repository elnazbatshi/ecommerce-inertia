<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class VehicleBrandResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vehicle_type_id' => $this->vehicle_type_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type,
            'country' => $this->country,
            'description' => $this->description,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'vehicles_count' => $this->whenCounted('vehicles'),
            'vehicle_type' => $this->whenLoaded('vehicleType', fn () => [
                'id' => $this->vehicleType?->id,
                'name' => $this->vehicleType?->name,
                'slug' => $this->vehicleType?->slug,
            ]),
            'logo_media_id' => $this->logo_media_id,
            'logo_media' => $this->whenLoaded('logoMedia', fn () => [
                'id' => $this->logoMedia?->id,
                'original_name' => $this->logoMedia?->original_name,
                'url' => $this->logoMedia?->path ? Storage::disk('public')->url($this->logoMedia->path) : null,
            ]),
        ];
    }
}
