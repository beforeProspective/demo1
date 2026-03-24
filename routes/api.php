<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TestController;
use App\Http\Middleware\JwtMiddleware;
use App\Http\Middleware\PermissionMiddleware;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/public', [TestController::class, 'publicEndpoint']);

    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
    });

    Route::middleware([JwtMiddleware::class])->group(function () {
        Route::prefix('auth')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
        });

        Route::get('/protected', [TestController::class, 'protectedEndpoint']);

        Route::middleware([RoleMiddleware::class . ':user'])->group(function () {
            Route::get('/user/dashboard', [TestController::class, 'userOnlyEndpoint']);
        });

        Route::middleware([RoleMiddleware::class . ':admin'])->group(function () {
            Route::get('/admin/dashboard', [TestController::class, 'adminOnlyEndpoint']);
        });

        Route::middleware([PermissionMiddleware::class . ':view_reports,manage_users'])
            ->get('/reports', [TestController::class, 'permissionRequiredEndpoint']);
    });
});
