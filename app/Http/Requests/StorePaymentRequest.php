<?php

namespace App\Http\Requests;

use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'method' => ['required', Rule::in(Payment::METHODS)],
            'gateway' => ['nullable', 'string', 'max:255'],
            'transaction_id' => ['nullable', 'string', 'max:255', 'unique:payments,transaction_id'],
            'reference_id' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(Payment::STATUSES)],
            'raw_response' => ['nullable', 'array'],
            'admin_note' => ['nullable', 'string'],
        ];
    }
}
