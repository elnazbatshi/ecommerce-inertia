<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerPurchasePaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'order_number' => $this->order_number,
            'method' => $this->method,
            'amount' => (float) $this->amount,
            'transaction_id' => $this->transaction_id,
            'reference_id' => $this->reference_id,
            'paid_at' => $this->paid_at,
            'status' => $this->status,
            'order_url' => route('admin.orders.show', $this->order_id, false),
        ];
    }
}
