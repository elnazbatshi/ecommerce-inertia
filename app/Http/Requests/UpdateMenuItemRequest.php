<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:menu_items,id'],
            'type' => ['required', Rule::in(['internal', 'external'])],
            'url' => [
                'nullable',
                'string',
                'max:255',
                Rule::requiredIf(fn () => $this->input('type') === 'external'),
            ],
            'route_name' => ['nullable', 'string', 'max:255'],
            'route_params' => ['nullable', 'json'],
            'target' => ['required', Rule::in(['_self', '_blank'])],
            'icon' => ['nullable', 'string', 'max:255'],
            'css_class' => ['nullable', 'string', 'max:255'],
            'rel' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->input('type') === 'internal' && ! $this->input('url') && ! $this->input('route_name')) {
                $validator->errors()->add('url', 'برای لینک داخلی باید آدرس یا نام مسیری وارد شود.');
                $validator->errors()->add('route_name', 'برای لینک داخلی باید آدرس یا نام مسیری وارد شود.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => filter_var($this->input('is_active'), FILTER_VALIDATE_BOOLEAN),
        ]);
    }
}
