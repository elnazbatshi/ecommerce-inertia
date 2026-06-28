<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBannerSectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'sort_order' => $this->input('sort_order') ?? 0,
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        $bannerSection = $this->route('bannerSection');

        return [
            'title' => ['required', 'string', 'max:255'],
            'key' => ['required', 'string', 'max:255', Rule::unique('banner_sections', 'key')->ignore($bannerSection?->id)],
            'placement' => ['required', Rule::in(['home_top', 'home_middle', 'home_bottom'])],
            'layout' => ['required', Rule::in(['full_width', 'two_columns', 'four_grid', 'mixed_grid', 'horizontal_scroll'])],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }
}
