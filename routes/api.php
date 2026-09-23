<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\HealthController;
use App\Http\Controllers\Admin\TenantController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function () {
    Route::get('health', [HealthController::class, 'index']);

    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1');
    Route::post('auth/logout', [AuthController::class, 'logout'])
        ->middleware('auth:sanctum');
    Route::get('auth/me', [AuthController::class, 'me'])
        ->middleware('auth:sanctum');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('dashboard/stats', [DashboardController::class, 'stats']);

        Route::apiResource('tenants', TenantController::class);
    });
});
