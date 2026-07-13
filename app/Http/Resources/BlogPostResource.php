<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class BlogPostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $imageUrl = $this->featured_image ? Storage::url($this->featured_image) : null;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'excerpt' => $this->excerpt,
            'content' => $this->content,
            'featured_image' => $this->featured_image,
            'featured_image_url' => $imageUrl,
            'featured_image_alt' => $this->featured_image_alt,
            'status' => $this->status,
            'status_label' => $this->statusLabel(),
            'is_featured' => $this->is_featured,
            'published_at' => $this->published_at?->format('Y-m-d H:i:s'),
            'views' => $this->views,
            'reading_time' => max(1, (int) ceil(str_word_count(strip_tags((string) $this->content)) / 200)),
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,
            'canonical_url' => $this->canonical_url,
            'author' => $this->whenLoaded('author', fn () => [
                'id' => $this->author?->id,
                'name' => $this->author?->name,
            ]),
            'category' => $this->whenLoaded('category', fn () => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ] : null),
            'tags' => $this->whenLoaded('tags', fn () => $this->tags->map(fn ($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
            ])->values()),
            'products' => $this->whenLoaded('products', fn () => $this->products->map(fn ($product) => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'image' => $product->main_image,
                'image_url' => $product->main_image ? Storage::url($product->main_image) : null,
                'sort_order' => $product->pivot?->sort_order,
            ])->values()),
            'tag_ids' => $this->whenLoaded('tags', fn () => $this->tags->pluck('id')->values()),
            'product_ids' => $this->whenLoaded('products', fn () => $this->products->pluck('id')->values()),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
