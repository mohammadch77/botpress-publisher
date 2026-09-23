<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\BotController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DestinationController;
use App\Http\Controllers\Admin\HealthController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function () {
    Route::get('health', [HealthController::class, 'index']);

    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1');
    Route::post('auth/logout', [AuthController::class, 'logout'])
        ->middleware('auth:sanctum');
    Route::get('auth/me', [AuthController::class, 'me'])
        ->middleware('auth:sanctum');

    Route::middleware(['auth:sanctum', 'tenant', 'tenant.active'])->group(function () {
        Route::get('dashboard/stats', [DashboardController::class, 'stats']);

        Route::apiResource('tenants', TenantController::class);

        Route::apiResource('users', UserController::class);
        Route::post('users/{user}/roles', [UserController::class, 'assignRole']);
        Route::delete('users/{user}/roles/{role}', [UserController::class, 'removeRole']);

        Route::apiResource('bots', BotController::class);
        Route::post('bots/{bot}/test-connection', [BotController::class, 'testConnection']);

        Route::apiResource('destinations', DestinationController::class);
        Route::post('destinations/{destination}/test-connection', [DestinationController::class, 'testConnection']);
        Route::get('destinations/{destination}/users', [DestinationController::class, 'users']);
        Route::post('destinations/{destination}/users', [DestinationController::class, 'addUser']);
        Route::delete('destinations/{destination}/users/{user}', [DestinationController::class, 'removeUser']);
    });
});
