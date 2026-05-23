<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;

Route::prefix('v1')->group(function () {
    // auth routes
    Route::middleware('guest')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
    });
    Route::post('/login', [AuthController::class, 'login']);
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
    });


    // user routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('/users', UserController::class)->middleware('is_admin')->withTrashed();
        Route::post('/users/{user}/restore', [UserController::class, 'restore'])->middleware('is_admin');
    });
});
