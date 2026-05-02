<?php

namespace App\Observers;

use App\Models\Brand;
use App\Services\SlugService;

class BrandObserver
{
    public function saving(Brand $brand): void
    {
        if (! $brand->exists || $brand->isDirty('slug')) {
            $source = filled($brand->slug) ? $brand->slug : $brand->name;
        } else {
            return;
        }

        $brand->slug = app(SlugService::class)->unique(Brand::class, $source, $brand->id);
    }
}
