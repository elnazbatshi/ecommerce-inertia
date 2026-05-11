<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBrandRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $brand = $this->route('brand');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('brands', 'slug')->ignore($brand?->id)],
            'logo' => ['nullable', 'integer', 'exists:media,id'],
            'remove_logo' => ['boolean'],
            'description' => ['nullable', 'string'],
            'content' => ['nullable', 'string'],
            'featured_image' => ['nullable', 'integer', 'exists:media,id'],
            'remove_featured_image' => ['boolean'],
            'cover_image' => ['nullable', 'integer', 'exists:media,id'],
            'remove_cover_image' => ['boolean'],
            'meta_title' => ['nullable', 'string', 'max:60'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'meta_keywords' => ['nullable', 'array'],
            'meta_keywords.*' => ['string', 'max:50'],
            'canonical_url' => ['nullable', 'url', 'max:255'],
            'seo_index' => ['boolean'],
            'seo_follow' => ['boolean'],
        ];
    }
}
