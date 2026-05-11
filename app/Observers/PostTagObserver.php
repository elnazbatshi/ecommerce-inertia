<?php

namespace App\Observers;

use App\Models\PostTag;
use App\Http\Services\SlugService;

class PostTagObserver
{
    public function saving(PostTag $postTag): void
    {
        if (! $postTag->exists || $postTag->isDirty('slug')) {
            $source = filled($postTag->slug) ? $postTag->slug : $postTag->name;
        } else {
            return;
        }

        $postTag->slug = app(SlugService::class)->unique(PostTag::class, $source, $postTag->id);
    }
}
