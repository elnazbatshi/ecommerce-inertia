<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Menu;

class StoreMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('menus', 'slug')],
            'location' => ['required', Rule::in(array_keys(Menu::locations()))],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
