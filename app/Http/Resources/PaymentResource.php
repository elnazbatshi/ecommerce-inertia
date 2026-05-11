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
            'created_at' => $this->created_at?->toDateTimeString(),
        ];

        if ($this->relationLoaded('order') && $this->order?->relationLoaded('items')) {
            $data['raw_response'] = $this->raw_response;
        }

        return $data;
    }
}
