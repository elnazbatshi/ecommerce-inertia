<?php

namespace App\Http\Requests;

use App\Models\Payment;
use Illuminate\Validation\Rule;

class UpdatePaymentRequest extends StorePaymentRequest
{
    public function rules(): array
    {
        $payment = $this->route('payment');

        return [
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'method' => ['required', Rule::in(Payment::METHODS)],
            'gateway' => ['nullable', 'string', 'max:255'],
            'transaction_id' => ['nullable', 'string', 'max:255', Rule::unique('payments', 'transaction_id')->ignore($payment?->id)],
            'reference_id' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(Payment::STATUSES)],
            'raw_response' => ['nullable', 'array'],
            'admin_note' => ['nullable', 'string'],
        ];
    }
}
