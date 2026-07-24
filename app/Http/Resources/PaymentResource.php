<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'order' => $this->when($this->relationLoaded('order') && $this->order, fn () => [
                'id' => $this->order->id,
                'order_number' => $this->order->order_number,
                'total' => $this->order->total,
                'status' => $this->order->status,
                'payment_status' => $this->order->payment_status,
                'customer' => $this->order->customer,
            ]),
            'customer' => $this->whenLoaded('customer'),
            'amount' => $this->amount,
            'method' => $this->method,
            'gateway' => $this->gateway,
            'transaction_id' => $this->transaction_id,
            'reference_id' => $this->reference_id,
            'status' => $this->status,
            'paid_at' => $this->paid_at?->toDateTimeString(),
            'admin_note' => $this->admin_note,
            'payment_method_id' => $this->payment_method_id,
            'currency' => $this->currency,
            'authority' => $this->authority,
            'initiated_at' => $this->initiated_at?->toDateTimeString(),
            'verified_at' => $this->verified_at?->toDateTimeString(),
            'failed_at' => $this->failed_at?->toDateTimeString(),
            'error_code' => $this->error_code,
            'error_message' => $this->error_message,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];

        if ($this->relationLoaded('order') && $this->order?->relationLoaded('items')) {
            $data['raw_response'] = $this->sanitizedPayload($this->raw_response ?? []);
        }

        return $data;
    }

    private function sanitizedPayload(array $payload): array
    {
        foreach ($payload as $key => $value) {
            if (is_array($value)) {
                $payload[$key] = $this->sanitizedPayload($value);
                continue;
            }

            if (preg_match('/secret|token|password|pin|key/i', (string) $key)) {
                $payload[$key] = $value ? '***' : $value;
            }
        }

        return $payload;
    }
}
