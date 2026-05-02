<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AccessController;
use App\Http\Controllers\AttributeController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect('/dashboard');
});

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
    Route::delete('/product-images/{image}', [ProductController::class, 'destroyImage'])->name('product-images.destroy');
    Route::delete('/product-variants/{variant}', [ProductController::class, 'destroyVariant'])->name('product-variants.destroy');
    Route::resource('categories', CategoryController::class)->except(['create', 'show', 'edit']);
    Route::resource('brands', BrandController::class)->except(['create', 'show', 'edit']);
    Route::resource('attributes', AttributeController::class)->except(['create', 'show', 'edit']);
});
