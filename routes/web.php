<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AccessController;
use App\Http\Controllers\AddressController;
use App\Http\Controllers\AttributeController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\MenuController;
use App\Http\Controllers\MenuItemController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PostCategoryController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\PostTagController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PublicContentController;
use Inertia\Inertia;

//Route::get('/', function () {
//    return redirect('/dashboard');
//});

Route::get('/', [HomeController::class, 'index'])->name('frontend.home');

Route::get('/blog/{post:slug}', [PublicContentController::class, 'post'])->name('public.blog.show');
Route::get('/brand/{brand:slug}', [PublicContentController::class, 'brand'])->name('public.brand.show');
Route::get('/page/{page:slug}', [PublicContentController::class, 'page'])->name('public.page.show');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware('auth')->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/accesses', [AccessController::class, 'index'])->name('accesses.index');
    Route::post('/accesses', [AccessController::class, 'store'])->name('accesses.store');
    Route::put('/accesses/{role}', [AccessController::class, 'update'])->name('accesses.update');

    Route::resource('products', ProductController::class);
    Route::resource('customers', CustomerController::class);
    Route::patch('/orders/{order}/status', [OrderController::class, 'changeStatus'])->name('orders.change-status');
    Route::patch('/orders/{order}/payment-status', [OrderController::class, 'changePaymentStatus'])->name('orders.change-payment-status');
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
    Route::resource('posts', PostController::class);
    Route::resource('post-categories', PostCategoryController::class)
        ->parameters(['post-categories' => 'post_category'])
        ->except(['create', 'show', 'edit']);
    Route::resource('post-tags', PostTagController::class)
        ->parameters(['post-tags' => 'post_tag'])
        ->except(['create', 'show', 'edit']);
    Route::resource('pages', PageController::class);

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
    Route::get('/api/menus/location/{location}', [MenuController::class, 'location'])->name('menus.location');

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
