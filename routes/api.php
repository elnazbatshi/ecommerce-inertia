<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\RolePermissionController;
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
