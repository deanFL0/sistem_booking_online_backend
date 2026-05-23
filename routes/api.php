<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ServicesController;

Route::prefix('v1')->group(function () {
    // auth routes
    Route::middleware('guest')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
    });
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
    });


    // routes for admin
    Route::middleware(['auth:sanctum', 'is_admin'])->group(function () {
        // user routes
        Route::apiResource('/users', UserController::class)->withTrashed();
        Route::post('/users/{user}/restore', [UserController::class, 'restore']);

        // service routes
        Route::apiResource('/services', ServicesController::class);
    });

    // routes for user
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/services', [ServicesController::class, 'index']);
        Route::get('/services/{service}', [ServicesController::class, 'show']);
    });
});
