<?php

namespace App\Observers;

use App\Models\Page;
use App\Http\Services\SlugService;

class PageObserver
{
    public function saving(Page $page): void
    {
        if (! $page->exists || $page->isDirty('slug')) {
            $source = filled($page->slug) ? $page->slug : $page->title;
        } else {
            return;
        }

        $page->slug = app(SlugService::class)->unique(Page::class, $source, $page->id);
    }
}
