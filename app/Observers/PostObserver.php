<?php

namespace App\Observers;

use App\Models\Post;
use App\Http\Services\SlugService;

class PostObserver
{
    public function saving(Post $post): void
    {
        if (! $post->exists || $post->isDirty('slug')) {
            $source = filled($post->slug) ? $post->slug : $post->title;
        } else {
            return;
        }

        $post->slug = app(SlugService::class)->unique(Post::class, $source, $post->id);
    }
}
