<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateShippingMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('is_active')) {
            $this->merge(['is_active' => filter_var($this->input('is_active'), FILTER_VALIDATE_BOOLEAN)]);
        }
    }

    public function rules(): array
    {
        $shippingMethodId = $this->route('shippingMethod')?->id ?? $this->route('shipping_method');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('shipping_methods', 'slug')->ignore($shippingMethodId)],
            'description' => ['nullable', 'string'],
            'type' => ['required', Rule::in(['fixed', 'free', 'weight_based', 'city_based', 'pickup'])],
            'base_cost' => ['nullable', 'numeric', 'min:0'],
            'free_from_amount' => ['nullable', 'numeric', 'min:0'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_order_amount' => ['nullable', 'numeric', 'min:0', 'gte:min_order_amount'],
            'estimated_delivery_days' => ['nullable', 'string', 'max:255'],
            'settings' => ['nullable', 'array'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }
}

