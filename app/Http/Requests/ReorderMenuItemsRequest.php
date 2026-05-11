<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

class ReorderMenuItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            '*.id' => ['required', 'integer', 'exists:menu_items,id'],
            '*.sort_order' => ['required', 'integer'],
            '*.children' => ['nullable', 'array'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            foreach ($this->all() as $item) {
                $this->validateRecursive($item, $validator);
            }
        });
    }

    private function validateRecursive(array $item, $validator): void
    {
        if (isset($item['children']) && is_array($item['children'])) {
            foreach ($item['children'] as $child) {
                if (! isset($child['id']) || ! is_int($child['id'])) {
                    $validator->errors()->add('items', 'Children items must contain valid ids.');
                }
                if (! isset($child['sort_order']) || ! is_int($child['sort_order'])) {
                    $validator->errors()->add('items', 'Children items must contain valid sort_order.');
                }
                $this->validateRecursive($child, $validator);
            }
        }
    }
}
