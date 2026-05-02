<?php

namespace App\Observers;

use App\Models\Category;
use App\Services\SlugService;

class CategoryObserver
{
    public function saving(Category $category): void
    {
        if (! $category->exists || $category->isDirty('slug')) {
            $source = filled($category->slug) ? $category->slug : $category->name;
        } else {
            return;
        }

        $category->slug = app(SlugService::class)->unique(Category::class, $source, $category->id);
    }
}
