<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('jwt.auth')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
    });
});

Route::middleware(['jwt.auth', 'role:admin'])->group(function () {
    Route::get('/admin/users', function () {
        return response()->json([
            'success' => true,
            'message' => 'Admin access granted - user list',
            'data' => []
        ]);
    });
});

Route::middleware(['jwt.auth', 'permission:system.settings.read'])->group(function () {
    Route::get('/settings', function () {
        return response()->json([
            'success' => true,
            'message' => 'Settings accessible',
        ]);
    });
});

Route::middleware(['jwt.auth', 'permission:users.read'])->group(function () {
    Route::get('/users', function () {
        return response()->json([
            'success' => true,
            'message' => 'Users list - requires users.read permission',
        ]);
    });
});

Route::middleware(['jwt.auth'])->group(function () {
    Route::get('/profile', function () {
        return response()->json([
            'success' => true,
            'message' => 'Profile accessible',
            'user' => auth()->user()
        ]);
    });
});