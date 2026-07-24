<?php

namespace App\Http\Resources;

use App\Payments\PaymentGatewayManager;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentMethodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $gatewayManager = app(PaymentGatewayManager::class);
        $metadata = $gatewayManager->metadata($this->driver);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'driver' => $this->driver,
            'fee_type' => $this->fee_type,
            'fee_value' => $this->fee_value,
            'min_amount' => $this->min_amount,
            'max_amount' => $this->max_amount,
            'settings' => $this->sanitizedSettings(),
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'provider_status' => $metadata,
            'created_at' => $this->created_at?->toDateTimeString(),
            'updated_at' => $this->updated_at?->toDateTimeString(),
        ];
    }

    private function sanitizedSettings(): array
    {
        $settings = $this->settings ?? [];

        foreach ($settings as $key => $value) {
            if (preg_match('/secret|token|password|pin|key/i', (string) $key)) {
                $settings[$key] = $value ? '***' : $value;
            }
        }

        return $settings;
    }
}
