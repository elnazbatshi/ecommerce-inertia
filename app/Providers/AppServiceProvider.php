<?php

namespace App\Providers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\Attribute;
use App\Observers\AttributeObserver;
use App\Observers\BrandObserver;
use App\Observers\CategoryObserver;
use App\Observers\PageObserver;
use App\Observers\ProductObserver;
use App\Services\Sms\SmsIrService;
use App\Services\Sms\SmsServiceInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SmsServiceInterface::class, SmsIrService::class);
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
        Page::observe(PageObserver::class);
    }
}
