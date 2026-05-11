<?php

namespace App\Observers;

use App\Models\PostCategory;
use App\Http\Services\SlugService;

class PostCategoryObserver
{
    public function saving(PostCategory $postCategory): void
    {
        if (! $postCategory->exists || $postCategory->isDirty('slug')) {
            $source = filled($postCategory->slug) ? $postCategory->slug : $postCategory->name;
        } else {
            return;
        }

        $postCategory->slug = app(SlugService::class)->unique(PostCategory::class, $source, $postCategory->id);
    }
}
