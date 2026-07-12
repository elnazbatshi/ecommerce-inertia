<?php

namespace App\Http\Services;

use App\Models\Attribute;
use App\Models\BlogCategory;
use App\Models\BlogTag;
use App\Models\Category;

class CatalogService
{
    public function attributes()
    {
        return Attribute::query()->with('values')->orderBy('name')->get();
    }

    public function categories()
    {
        return Category::query()->with('parent:id,name,slug')->orderBy('name')->get();
    }

    public function categoryOptions()
    {
        return Category::query()->select(['id', 'name', 'slug'])->orderBy('name')->get();
    }

    public function postCategories()
    {
        return BlogCategory::query()->withCount('posts')->orderBy('name')->get();
    }

    public function postTags()
    {
        return BlogTag::query()->withCount('posts')->orderBy('name')->get();
    }
}
