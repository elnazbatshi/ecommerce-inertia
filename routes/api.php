<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\FrontendCatalogController;
use App\Http\Controllers\Api\BannerSectionController;
use App\Http\Controllers\Api\VehicleFinderController;
use App\Http\Controllers\Api\HeroSliderController;
use App\Http\Controllers\Api\RolePermissionController;
use App\Http\Controllers\Api\SiteSettingController;
use App\Http\Controllers\Api\SiteSearchController;
use App\Models\Menu;
use App\Services\MenuService;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::middleware('auth:sanctum')->get('/dashboard', function () {
    return response()->json([
        'data' => [
            'message' => 'Dashboard API is protected.',
        ],
    ]);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('roles-with-permissions', [RolePermissionController::class, 'getAllRolesWithPermissions']);
    Route::post('roles-with-permissions', [RolePermissionController::class, 'storeRolePermission']);
    Route::put('role/{role}', [RolePermissionController::class, 'updateRolePermission']);
    Route::get('permissions', [RolePermissionController::class, 'getPermissions']);
    Route::get('roles', [RolePermissionController::class, 'getRoles']);
});

Route::get('menus/{location}', fn (string $location, MenuService $menus) => response()->json($menus->getByLocation($location)))
    ->whereIn('location', array_keys(Menu::locations()));

Route::get('hero-sliders', [HeroSliderController::class, 'index'])->name('hero-sliders.index');

Route::prefix('frontend')->name('frontend.')->group(function () {
    Route::get('hero-sliders', [HeroSliderController::class, 'index'])->name('hero-sliders.index');
    Route::get('banner-sections', [BannerSectionController::class, 'index'])->name('banner-sections.index');
    Route::get('menu', [FrontendCatalogController::class, 'menu'])->name('menu');
    Route::get('categories', [FrontendCatalogController::class, 'categories'])->name('categories');
    Route::get('brands', [FrontendCatalogController::class, 'brands'])->name('brands');
    Route::get('site-settings', [SiteSettingController::class, 'index'])->name('site-settings.index');
Route::get('vehicles/popular', [FrontendCatalogController::class, 'popularVehicles'])->name('vehicles.popular');
Route::get('vehicle-finder/types', [VehicleFinderController::class, 'types'])->name('vehicle-finder.types');
Route::get('vehicle-finder/brands', [VehicleFinderController::class, 'brands'])->name('vehicle-finder.brands');
Route::get('vehicle-finder/vehicles', [VehicleFinderController::class, 'vehicles'])->name('vehicle-finder.vehicles');
    Route::get('search/suggestions', [FrontendCatalogController::class, 'searchSuggestions'])->name('search.suggestions');
});

Route::get('search/suggestions', [SiteSearchController::class, 'suggestions'])->name('site.search.suggestions');
Route::get('search', [SiteSearchController::class, 'search'])->name('site.search');
