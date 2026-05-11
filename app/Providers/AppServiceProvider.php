<?php

namespace App\Providers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\PostTag;
use App\Models\Product;
use App\Models\Attribute;
use App\Observers\AttributeObserver;
use App\Observers\BrandObserver;
use App\Observers\CategoryObserver;
use App\Observers\PageObserver;
use App\Observers\PostCategoryObserver;
use App\Observers\PostObserver;
use App\Observers\PostTagObserver;
use App\Observers\ProductObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Product::observe(ProductObserver::class);
        Category::observe(CategoryObserver::class);
        Brand::observe(BrandObserver::class);
        Attribute::observe(AttributeObserver::class);
        Post::observe(PostObserver::class);
        PostCategory::observe(PostCategoryObserver::class);
        PostTag::observe(PostTagObserver::class);
        Page::observe(PageObserver::class);
    }
}
