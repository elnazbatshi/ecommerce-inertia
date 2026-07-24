<?php

use App\Http\Controllers\AccessController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\AttributeController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\BannerSectionController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\CustomerPurchaseLedgerController;
use App\Http\Controllers\Frontend\CartController as FrontendCartController;
use App\Http\Controllers\Frontend\CheckoutController;
use App\Http\Controllers\Frontend\OrderConfirmationController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\CustomerAuthController;
use App\Http\Controllers\Frontend\PaymentController as FrontendPaymentController;
use App\Http\Controllers\Frontend\Profile\AddressController as FrontendProfileAddressController;
use App\Http\Controllers\Frontend\Profile\OrderController as FrontendProfileOrderController;
use App\Http\Controllers\Frontend\Profile\WishlistController as FrontendProfileWishlistController;
use App\Http\Controllers\Frontend\ProductReviewController as FrontendProductReviewController;
use App\Http\Controllers\Frontend\WishlistController;
use App\Http\Controllers\HeroSliderController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\MenuItemController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductSalesLedgerController;
use App\Http\Controllers\ProductReviewController;
use App\Http\Controllers\PublicContentController;
use App\Http\Controllers\SearchLogController;
use App\Http\Controllers\SearchSuggestionController;
use App\Http\Controllers\SiteSettingController;
use App\Http\Controllers\Admin\VehicleBrandController as AdminVehicleBrandController;
use App\Http\Controllers\Admin\VehicleController as AdminVehicleController;
use App\Http\Controllers\Admin\VehicleTypeController as AdminVehicleTypeController;
use App\Http\Controllers\Admin\ShippingMethodController as AdminShippingMethodController;
use App\Http\Controllers\Admin\PaymentMethodController as AdminPaymentMethodController;
use App\Http\Controllers\Admin\ProvinceController as AdminProvinceController;
use App\Http\Controllers\Admin\CityController as AdminCityController;
use App\Http\Controllers\Admin\BlogCategoryController as AdminBlogCategoryController;
use App\Http\Controllers\Admin\BlogPostController as AdminBlogPostController;
use App\Http\Controllers\Admin\BlogTagController as AdminBlogTagController;
use App\Http\Controllers\Frontend\BlogController as FrontendBlogController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [HomeController::class, 'index'])->name('site.home');
Route::get('/cart', fn () => Inertia::render('Frontend/Cart/Index'))->name('site.cart');
Route::post('/customer/auth/otp', [CustomerAuthController::class, 'requestOtp'])->name('site.customer-auth.otp');
Route::post('/customer/auth/verify', [CustomerAuthController::class, 'verifyOtp'])->name('site.customer-auth.verify');
Route::post('/customer/logout', [CustomerAuthController::class, 'logout'])->name('customer.logout');
Route::post('/cart/sync', [FrontendCartController::class, 'sync'])->name('frontend.cart.sync');
Route::get('/checkout', [CheckoutController::class, 'index'])->name('frontend.checkout.index');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('frontend.checkout.store');
Route::get('/payments/{payment}/start', [FrontendPaymentController::class, 'start'])->name('frontend.payments.start');
Route::get('/payments/{payment}/fake/success', [FrontendPaymentController::class, 'fakeSuccess'])->name('frontend.payments.fake.success');
Route::get('/payments/{payment}/fake/fail', [FrontendPaymentController::class, 'fakeFail'])->name('frontend.payments.fake.fail');
Route::get('/order/thank-you/{order}', [OrderConfirmationController::class, 'show'])->name('frontend.orders.thank-you');
Route::get('/profile/orders', [FrontendProfileOrderController::class, 'index'])->name('frontend.profile.orders');
Route::get('/profile/wishlist', [FrontendProfileWishlistController::class, 'index'])->name('profile.wishlist');
Route::get('/profile/addresses', [FrontendProfileAddressController::class, 'index'])->name('frontend.profile.addresses');
Route::post('/profile/addresses', [FrontendProfileAddressController::class, 'store'])->name('frontend.profile.addresses.store');
Route::put('/profile/addresses/{address}', [FrontendProfileAddressController::class, 'update'])->name('frontend.profile.addresses.update');
Route::delete('/profile/addresses/{address}', [FrontendProfileAddressController::class, 'destroy'])->name('frontend.profile.addresses.destroy');
Route::patch('/profile/addresses/{address}/default', [FrontendProfileAddressController::class, 'setDefault'])->name('frontend.profile.addresses.default');
Route::post('/wishlist/products/{product:slug}/toggle', [WishlistController::class, 'toggle'])->name('wishlist.products.toggle');
Route::get('/products', [PublicContentController::class, 'products'])->name('site.products.index');
Route::get('/products/{product:slug}', [PublicContentController::class, 'product'])->name('site.products.show');
Route::post('/products/{product:slug}/reviews', [FrontendProductReviewController::class, 'store'])->name('site.products.reviews.store');
Route::patch('/products/{product:slug}/reviews', [FrontendProductReviewController::class, 'update'])->name('site.products.reviews.update');
Route::get('/category/{category:slug}', [PublicContentController::class, 'category'])->name('site.categories.show');
Route::get('/brand/{brand:slug}', [PublicContentController::class, 'brand'])->name('site.brands.show');
Route::get('/blog', [FrontendBlogController::class, 'index'])->name('blog.index');
Route::get('/blog/category/{blogCategory:slug}', [FrontendBlogController::class, 'category'])->name('blog.category');
Route::get('/blog/tag/{blogTag:slug}', [FrontendBlogController::class, 'tag'])->name('blog.tag');
Route::get('/blog/{blogPost:slug}', [FrontendBlogController::class, 'show'])->name('blog.show');
Route::get('/page/{page:slug}', [PublicContentController::class, 'page'])->name('site.pages.show');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', function () {
            return Inertia::render('Dashboard');
        })->name('dashboard');

        Route::get('/site-settings', [SiteSettingController::class, 'index'])->name('site-settings.index');
        Route::put('/site-settings', [SiteSettingController::class, 'update'])->name('site-settings.update');

        Route::get('/accesses', [AccessController::class, 'index'])->name('accesses.index');
        Route::post('/accesses', [AccessController::class, 'store'])->name('accesses.store');
        Route::put('/accesses/{role}', [AccessController::class, 'update'])->name('accesses.update');

        Route::get('/product-reviews', [ProductReviewController::class, 'index'])->name('product-reviews.index');
        Route::patch('/product-reviews/{productReview}/approve', [ProductReviewController::class, 'approve'])->name('product-reviews.approve');
        Route::patch('/product-reviews/{productReview}/reject', [ProductReviewController::class, 'reject'])->name('product-reviews.reject');
        Route::delete('/product-reviews/{productReview}', [ProductReviewController::class, 'destroy'])->name('product-reviews.destroy');
        Route::patch('/products/{product:slug}/featured', [ProductController::class, 'toggleFeatured'])->name('products.featured');
        Route::resource('products', ProductController::class);
        Route::resource('customers', CustomerController::class);
        Route::patch('/orders/{order}/status', [OrderController::class, 'changeStatus'])->name('orders.change-status');
        Route::patch('/orders/{order}/payment-status', [OrderController::class, 'changePaymentStatus'])->name('orders.change-payment-status');
        Route::get('/product-sales-ledger', [ProductSalesLedgerController::class, 'index'])->name('product-sales-ledger.index');
        Route::get('/customer-purchase-ledger', [CustomerPurchaseLedgerController::class, 'index'])->name('customer-purchase-ledger.index');
        Route::resource('orders', OrderController::class);
        Route::patch('/payments/{payment}/status', [PaymentController::class, 'changeStatus'])->name('payments.change-status');
        Route::patch('/payments/{payment}/refund', [PaymentController::class, 'refund'])->name('payments.refund');
        Route::resource('payments', PaymentController::class)->except(['create', 'edit']);
        Route::post('/customers/{customer}/addresses', [AddressController::class, 'store'])->name('customers.addresses.store');
        Route::put('/customers/{customer}/addresses/{address}', [AddressController::class, 'update'])->name('customers.addresses.update');
        Route::delete('/customers/{customer}/addresses/{address}', [AddressController::class, 'destroy'])->name('customers.addresses.destroy');
        Route::patch('/customers/{customer}/addresses/{address}/default', [AddressController::class, 'setDefault'])->name('customers.addresses.default');
        Route::delete('/product-images/{image}', [ProductController::class, 'destroyImage'])->name('product-images.destroy');
        Route::delete('/product-variants/{variant}', [ProductController::class, 'destroyVariant'])->name('product-variants.destroy');
        Route::resource('categories', CategoryController::class)->except(['create', 'show', 'edit']);
        Route::resource('brands', BrandController::class)->except(['create', 'show', 'edit']);
        Route::resource('attributes', AttributeController::class)->except(['create', 'show', 'edit']);
        Route::patch('/vehicle-types/{vehicleType}/toggle-status', [AdminVehicleTypeController::class, 'toggleStatus'])->name('vehicle-types.toggle-status');
        Route::resource('vehicle-types', AdminVehicleTypeController::class)
            ->parameters(['vehicle-types' => 'vehicleType'])
            ->except(['show']);
        Route::patch('/vehicle-brands/{vehicleBrand}/toggle-status', [AdminVehicleBrandController::class, 'toggleStatus'])->name('vehicle-brands.toggle-status');
        Route::resource('vehicle-brands', AdminVehicleBrandController::class)
            ->parameters(['vehicle-brands' => 'vehicleBrand'])
            ->except(['show']);
        Route::patch('/vehicles/{vehicle}/toggle-status', [AdminVehicleController::class, 'toggleStatus'])->name('vehicles.toggle-status');
        Route::resource('vehicles', AdminVehicleController::class)->except(['show']);
        Route::get('/api/vehicles/options', [AdminVehicleController::class, 'options'])->name('vehicles.options');
        Route::patch('/shipping-methods/{shippingMethod}/toggle-status', [AdminShippingMethodController::class, 'toggleStatus'])->name('shipping-methods.toggle-status');
        Route::resource('shipping-methods', AdminShippingMethodController::class)
            ->parameters(['shipping-methods' => 'shippingMethod'])
            ->except(['show']);
        Route::get('/api/shipping-methods/options', [AdminShippingMethodController::class, 'options'])->name('shipping-methods.options');
        Route::patch('/payment-methods/{paymentMethod}/toggle-status', [AdminPaymentMethodController::class, 'toggleStatus'])->name('payment-methods.toggle-status');
        Route::resource('payment-methods', AdminPaymentMethodController::class)
            ->parameters(['payment-methods' => 'paymentMethod'])
            ->except(['show']);
        Route::get('/api/payment-methods/options', [AdminPaymentMethodController::class, 'options'])->name('payment-methods.options');
        Route::patch('/provinces/{province}/toggle-status', [AdminProvinceController::class, 'toggleStatus'])->name('provinces.toggle-status');
        Route::resource('provinces', AdminProvinceController::class)->except(['show']);
        Route::get('/api/provinces/options', [AdminProvinceController::class, 'options'])->name('provinces.options');
        Route::patch('/cities/{city}/toggle-status', [AdminCityController::class, 'toggleStatus'])->name('cities.toggle-status');
        Route::resource('cities', AdminCityController::class)->except(['show']);
        Route::get('/api/cities/options', [AdminCityController::class, 'options'])->name('cities.options');
        Route::patch('/blog-posts/{blogPost:slug}/featured', [AdminBlogPostController::class, 'toggleFeatured'])->name('blog-posts.featured');
        Route::resource('blog-posts', AdminBlogPostController::class)
            ->parameters(['blog-posts' => 'blogPost'])
            ->except(['show']);
        Route::resource('blog-categories', AdminBlogCategoryController::class)
            ->parameters(['blog-categories' => 'blogCategory'])
            ->except(['show']);
        Route::resource('blog-tags', AdminBlogTagController::class)
            ->parameters(['blog-tags' => 'blogTag'])
            ->except(['show']);
        Route::resource('pages', PageController::class);
        Route::post('/hero-sliders/reorder', [HeroSliderController::class, 'reorder'])->name('hero-sliders.reorder');
        Route::patch('/hero-sliders/{heroSlider}/toggle-status', [HeroSliderController::class, 'toggleStatus'])->name('hero-sliders.toggle-status');
        Route::resource('hero-sliders', HeroSliderController::class)
            ->parameters(['hero-sliders' => 'heroSlider'])
            ->except(['show']);
        Route::resource('banner-sections', BannerSectionController::class)
            ->parameters(['banner-sections' => 'bannerSection'])
            ->except(['show']);
        Route::get('/banner-sections/{bannerSection}/banners', [BannerController::class, 'index'])->name('banner-sections.banners.index');
        Route::get('/banner-sections/{bannerSection}/banners/create', [BannerController::class, 'create'])->name('banner-sections.banners.create');
        Route::post('/banner-sections/{bannerSection}/banners', [BannerController::class, 'store'])->name('banner-sections.banners.store');
        Route::get('/banners/{banner}/edit', [BannerController::class, 'edit'])->name('banners.edit');
        Route::put('/banners/{banner}', [BannerController::class, 'update'])->name('banners.update');
        Route::patch('/banners/{banner}', [BannerController::class, 'update']);
        Route::delete('/banners/{banner}', [BannerController::class, 'destroy'])->name('banners.destroy');

        Route::get('/search/suggestions', [SearchSuggestionController::class, 'index'])->name('search.suggestions.index');
        Route::post('/search/suggestions', [SearchSuggestionController::class, 'store'])->name('search.suggestions.store');
        Route::put('/search/suggestions/{suggestion}', [SearchSuggestionController::class, 'update'])->name('search.suggestions.update');
        Route::delete('/search/suggestions/{suggestion}', [SearchSuggestionController::class, 'destroy'])->name('search.suggestions.destroy');
        Route::post('/search/suggestions/reorder', [SearchSuggestionController::class, 'reorder'])->name('search.suggestions.reorder');
        Route::patch('/search/suggestions/{suggestion}/toggle', [SearchSuggestionController::class, 'toggleStatus'])->name('search.suggestions.toggle');
        Route::get('/search/autocomplete/products', [SearchSuggestionController::class, 'autocompleteProducts'])->name('search.autocomplete.products');
        Route::get('/search/autocomplete/categories', [SearchSuggestionController::class, 'autocompleteCategories'])->name('search.autocomplete.categories');
        Route::get('/search/autocomplete/brands', [SearchSuggestionController::class, 'autocompleteBrands'])->name('search.autocomplete.brands');

        Route::get('/search/logs', [SearchLogController::class, 'index'])->name('search.logs.index');
        Route::get('/search/logs/{searchLog}', [SearchLogController::class, 'show'])->name('search.logs.show');
        Route::delete('/search/logs/{searchLog}', [SearchLogController::class, 'destroy'])->name('search.logs.destroy');

        Route::get('/menus', [MenuController::class, 'builder'])->name('menus.index');
        Route::get('/menus/{menu:slug}', [MenuController::class, 'builder'])->name('menus.builder');
        Route::post('/menus', [MenuController::class, 'store'])->name('menus.store');
        Route::put('/menus/{menu:slug}', [MenuController::class, 'update'])->name('menus.update');
        Route::delete('/menus/{menu:slug}', [MenuController::class, 'destroy'])->name('menus.destroy');
        Route::get('/menus/{menu:slug}/tree', [MenuController::class, 'show'])->name('menus.show');
        Route::post('/menus/{menu:slug}/items', [MenuItemController::class, 'store'])->name('menus.items.store');
        Route::put('/menus/{menu:slug}/items/{item}', [MenuItemController::class, 'update'])->name('menus.items.update');
        Route::delete('/menus/{menu:slug}/items/{item}', [MenuItemController::class, 'destroy'])->name('menus.items.destroy');
        Route::patch('/menus/{menu:slug}/items/{item}/toggle', [MenuItemController::class, 'toggleStatus'])->name('menus.items.toggle');
        Route::post('/menus/{menu:slug}/items/reorder', [MenuItemController::class, 'reorder'])->name('menus.items.reorder');

        Route::prefix('media')->name('media.')->group(function () {
            Route::get('/', [MediaController::class, 'index'])->name('index');
            Route::post('/upload', [MediaController::class, 'upload'])->name('upload');
            Route::put('/{media}', [MediaController::class, 'update'])->name('update');
            Route::delete('/{media}', [MediaController::class, 'destroy'])->name('destroy');
            Route::post('/attach', [MediaController::class, 'attach'])->name('attach');
            Route::post('/detach', [MediaController::class, 'detach'])->name('detach');
            Route::post('/reorder', [MediaController::class, 'reorder'])->name('reorder');
            Route::post('/bulk-delete', [MediaController::class, 'bulkDelete'])->name('bulk-delete');
        });
    });
