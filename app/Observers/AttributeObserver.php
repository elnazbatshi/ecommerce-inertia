<?php

namespace App\Observers;

use App\Models\Attribute;
use App\Http\Services\SlugService;

class AttributeObserver
{
    public function saving(Attribute $attribute): void
    {
        if (! $attribute->exists || $attribute->isDirty('slug')) {
            $source = filled($attribute->slug) ? $attribute->slug : $attribute->name;
        } else {
            return;
        }

        $attribute->slug = app(SlugService::class)->unique(Attribute::class, $source, $attribute->id);
    }
}
