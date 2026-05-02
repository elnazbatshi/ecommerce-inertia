<?php

namespace App\Observers;

use App\Models\Product;
use App\Services\SlugService;

class ProductObserver
{
    public function saving(Product $product): void
    {
        if (! $product->exists || $product->isDirty('slug')) {
            $source = filled($product->slug) ? $product->slug : $product->name;
        } else {
            return;
        }

        $product->slug = app(SlugService::class)->unique(Product::class, $source, $product->id);
    }
}
