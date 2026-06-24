<?php

namespace App\Http\Resources;

use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'meta_keywords' => $this->meta_keywords,
            'canonical_url' => $this->canonical_url,
            'seo_index' => $this->seo_index,
            'seo_follow' => $this->seo_follow,
            'description' => $this->description,
            'brand_id' => $this->brand_id,
            'category_id' => $this->category_id,
            'brand' => $this->whenLoaded('brand'),
            'category' => $this->whenLoaded('category'),
            'related_posts' => $this->whenLoaded('relatedPosts'),
            'material' => $this->material,
            'origin' => $this->origin,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'price' => $this->price,
            'discount_price' => $this->discount_price,
            'currency' => $this->currency,
            'main_image' => $this->main_image,
            'main_image_url' => $this->main_image ? Storage::url($this->main_image) : null,
            'status' => $this->status,
            'is_featured' => $this->is_featured,
            'type' => $this->type,
            'stock' => $this->stock,
        ];

        if ($this->relationLoaded('images')) {
            $data['images'] = $this->images->map(fn (ProductImage $image) => [
                'id' => $image->id,
                'image' => $image->image,
                'url' => Storage::url($image->image),
                'is_main' => $image->is_main,
                'sort_order' => $image->sort_order,
            ])->values();
        }

        if ($this->relationLoaded('variants')) {
            $data['variants'] = $this->variants->map(fn (ProductVariant $variant) => [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'price' => $variant->price,
                'discount_price' => $variant->discount_price,
                'stock' => $variant->stock,
                'image' => $variant->image,
                'image_url' => $variant->image ? Storage::url($variant->image) : null,
                'remove_image' => false,
                'attribute_values' => $variant->relationLoaded('attributeValues')
                    ? $variant->attributeValues->pluck('id')->values()
                    : [],
            ])->values();
        }

        if ($this->relationLoaded('relatedPosts')) {
            $data['related_post_ids'] = $this->relatedPosts->pluck('id')->values();
        }
        if ($this->relationLoaded('vehicles')) {
            $data['vehicle_ids'] = $this->vehicles->pluck('id')->values();
        }

        return $data;
    }
}
