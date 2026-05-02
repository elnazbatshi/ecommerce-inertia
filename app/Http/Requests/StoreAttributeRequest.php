<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttributeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $attribute = $this->route('attribute');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('attributes', 'slug')->ignore($attribute?->id)],
            'type' => ['nullable', 'string', 'max:255'],
            'values' => ['nullable', 'array'],
            'values.*.id' => ['nullable', 'integer', 'exists:attribute_values,id'],
            'values.*.value' => ['required_with:values', 'string', 'max:255'],
            'values.*.slug' => ['nullable', 'string', 'max:255'],
        ];
    }
}
