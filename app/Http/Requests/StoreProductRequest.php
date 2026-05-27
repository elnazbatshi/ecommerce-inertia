<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:products,slug'],
            'meta_title' => ['nullable', 'string', 'max:60'],
            'meta_description' => ['nullable', 'string', 'max:160'],
            'meta_keywords' => ['nullable', 'array'],
            'meta_keywords.*' => ['string', 'max:50'],
            'canonical_url' => ['nullable', 'url', 'max:255'],
            'seo_index' => ['boolean'],
            'seo_follow' => ['boolean'],
            'description' => ['nullable', 'string'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'material' => ['nullable', 'string', 'max:255'],
            'origin' => ['nullable', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:255', 'unique:products,sku'],
            'barcode' => ['nullable', 'string', 'max:255', 'unique:products,barcode'],
            'price' => ['required', 'numeric', 'min:0'],
            'discount_price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:8'],
            'main_image' => ['nullable', 'integer', 'exists:media,id'],
            'gallery_images' => ['nullable', 'array'],
            'gallery_images.*' => ['nullable', 'integer', 'exists:media,id'],
            'status' => ['required', Rule::in(['active', 'inactive', 'draft'])],
            'type' => ['required', Rule::in(['simple', 'variable'])],
            'stock' => ['nullable', 'integer', 'min:0', 'required_if:type,simple'],
            'related_post_ids' => ['nullable', 'array'],
            'related_post_ids.*' => ['integer', 'exists:posts,id'],
            'vehicle_ids' => ['nullable', 'array'],
            'vehicle_ids.*' => ['integer', 'exists:vehicles,id'],
            'variants' => ['nullable', 'array', 'required_if:type,variable'],
            'variants.*.id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'variants.*.sku' => ['nullable', 'string', 'max:255', 'distinct', 'unique:product_variants,sku'],
            'variants.*.price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.discount_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.stock' => ['required_if:type,variable', 'integer', 'min:0'],
            'variants.*.image' => ['nullable', 'integer', 'exists:media,id'],
            'variants.*.remove_image' => ['boolean'],
            'variants.*.attribute_values' => ['nullable', 'array'],
            'variants.*.attribute_values.*' => ['integer', 'exists:attribute_values,id'],
        ];
    }
}
