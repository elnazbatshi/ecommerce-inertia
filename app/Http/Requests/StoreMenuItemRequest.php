<?php

namespace App\Http\Requests;

use App\Models\MenuItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMenuItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'title_attribute' => ['nullable', 'string', 'max:255'],
            'parent_id' => ['nullable', 'integer', 'exists:menu_items,id'],
            'type' => ['required', Rule::in(MenuItem::TYPES)],
            'reference_id' => [
                'nullable',
                'integer',
                Rule::requiredIf(fn () => in_array($this->input('type'), ['page', 'category', 'product', 'brand', 'post'], true)),
            ],
            'url' => [
                'nullable',
                'string',
                'max:2048',
                Rule::requiredIf(fn () => in_array($this->input('type'), ['custom', 'external'], true)),
            ],
            'route_name' => ['nullable', 'string', 'max:255'],
            'route_params' => ['nullable', 'array'],
            'target' => ['required', Rule::in(['_self', '_blank'])],
            'icon' => ['nullable', 'string', 'max:255'],
            'css_class' => ['nullable', 'string', 'max:255'],
            'rel' => ['nullable', 'string', 'max:255'],
            'depth' => ['nullable', 'integer', 'min:0'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'auto_children' => ['boolean'],
            'children_source' => ['nullable', Rule::in(MenuItem::CHILDREN_SOURCES), 'required_if:auto_children,true'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => filter_var($this->input('is_active', true), FILTER_VALIDATE_BOOLEAN),
            'auto_children' => filter_var($this->input('auto_children', false), FILTER_VALIDATE_BOOLEAN),
            'route_params' => is_string($this->input('route_params'))
                ? json_decode($this->input('route_params'), true)
                : $this->input('route_params'),
        ]);
    }
}
