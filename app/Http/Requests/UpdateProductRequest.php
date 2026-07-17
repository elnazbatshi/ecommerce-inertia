<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $product = $this->route('product');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($product?->id)],
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
            'sku' => ['nullable', 'string', 'max:255', Rule::unique('products', 'sku')->ignore($product?->id)],
            'barcode' => ['nullable', 'string', 'max:255', Rule::unique('products', 'barcode')->ignore($product?->id)],
            'price' => ['required', 'numeric', 'min:0'],
            'discount_price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:8'],
            'main_image' => ['nullable', 'integer', 'exists:media,id'],
            'remove_main_image' => ['boolean'],
            'gallery_images' => ['nullable', 'array'],
            'gallery_images.*' => ['nullable', 'integer', 'exists:media,id'],
            'status' => ['required', Rule::in(['active', 'inactive', 'draft'])],
            'is_featured' => ['boolean'],
            'type' => ['required', Rule::in(['simple', 'variable'])],
            'stock' => ['nullable', 'integer', 'min:0', 'required_if:type,simple'],
            'related_post_ids' => ['nullable', 'array'],
            'related_post_ids.*' => ['integer', 'exists:blog_posts,id'],
            'vehicle_ids' => ['nullable', 'array'],
            'vehicle_ids.*' => ['integer', 'exists:vehicles,id'],
            'variants' => ['nullable', 'array', 'required_if:type,variable'],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.sku' => ['nullable', 'string', 'max:255', 'distinct'],
            'variants.*.price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.discount_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.stock' => ['required_if:type,variable', 'integer', 'min:0'],
            'variants.*.image' => ['nullable', 'integer', 'exists:media,id'],
            'variants.*.remove_image' => ['boolean'],
            'variants.*.attribute_values' => ['nullable', 'array'],
            'variants.*.attribute_values.*' => ['integer', 'exists:attribute_values,id'],
            'deleted_image_ids' => ['nullable', 'array'],
            'deleted_image_ids.*' => ['integer', 'exists:product_images,id'],
            'deleted_variant_ids' => ['nullable', 'array'],
            'deleted_variant_ids.*' => ['integer', 'exists:product_variants,id'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            foreach ($this->input('variants', []) as $index => $variant) {
                if (blank($variant['sku'] ?? null)) {
                    continue;
                }

                $exists = \App\Models\ProductVariant::query()
                    ->where('sku', $variant['sku'])
                    ->when($variant['id'] ?? null, fn ($query, $id) => $query->whereKeyNot($id))
                    ->exists();

                if ($exists) {
                    $validator->errors()->add("variants.{$index}.sku", 'این SKU قبلاً برای تنوع دیگری ثبت شده است.');
                }

                if (filled($variant['id'] ?? null)) {
                    $belongsToProduct = \App\Models\ProductVariant::query()
                        ->whereKey($variant['id'])
                        ->where('product_id', $this->route('product')?->id)
                        ->exists();

                    if (! $belongsToProduct) {
                        $validator->errors()->add("variants.{$index}.id", 'این تنوع متعلق به محصول فعلی نیست.');
                    }
                }
            }
        });
    }
}
