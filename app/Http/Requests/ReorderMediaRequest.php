<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderMediaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array'],
            'items.*.media_id' => ['required', 'integer', 'exists:media,id'],
            'items.*.mediable_type' => ['required', Rule::in(['product', 'brand', 'post', 'page'])],
            'items.*.mediable_id' => ['required', 'integer'],
            'items.*.collection' => ['nullable', 'string', 'max:100'],
            'items.*.sort_order' => ['required', 'integer'],
        ];
    }
}
