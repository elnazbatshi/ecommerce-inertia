<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePageRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $page = $this->route('page');

        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('pages', 'slug')->ignore($page?->id)],
            'content' => ['required', 'string'],
            'featured_image' => ['nullable', 'integer', 'exists:media,id'],
            'remove_featured_image' => ['boolean'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'published_at' => ['nullable', 'date'],
            'template' => ['nullable', 'string', 'max:80'],
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
