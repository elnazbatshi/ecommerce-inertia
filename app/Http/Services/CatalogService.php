<?php

namespace App\Http\Services;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\PostCategory;
use App\Models\PostTag;

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
        return PostCategory::query()->withCount('posts')->orderBy('name')->get();
    }

    public function postTags()
    {
        return PostTag::query()->withCount('posts')->orderBy('name')->get();
    }
}
