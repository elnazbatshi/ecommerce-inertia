<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReorderMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array'],
            'items.*.id' => ['required', 'integer', 'exists:menu_items,id'],
            'items.*.children' => ['nullable', 'array'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            foreach ($this->input('items', []) as $item) {
                $this->validateNode($item, $validator);
            }
        });
    }

    private function validateNode(array $item, $validator): void
    {
        foreach ($item['children'] ?? [] as $child) {
            if (! isset($child['id']) || ! is_numeric($child['id'])) {
                $validator->errors()->add('items', 'هر آیتم منو باید شناسه معتبر داشته باشد.');
            }

            $this->validateNode($child, $validator);
        }
    }
}
