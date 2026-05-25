<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\OperationalHourController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\ResourceController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ResourceAvailabilityOverrideController;

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
        Route::apiResource('/services', ServiceController::class);

        // resource routes
        Route::apiResource('/resources', ResourceController::class);

        // operational hour routes
        Route::scopeBindings()->group(function () {
            Route::apiResource('resources.operational-hours', OperationalHourController::class)
            ->parameters([
                'operational-hours' => 'operationalHour'
            ]);
        });

        // resource availability override routes
        Route::apiResource('/availability-overrides', ResourceAvailabilityOverrideController::class)
        ->parameters([
            'availability-overrides' => 'resourceAvailabilityOverride'
        ]);

        // bookings routes
        Route::apiResource('/bookings', BookingController::class);
    });

    // routes for user
    Route::middleware('auth:sanctum')->group(function () {
        // profile routes
        Route::get('/profile', [ProfileController::class, 'me']);
        Route::put('/profile', [ProfileController::class, 'update']);
        Route::patch('/profile', [ProfileController::class, 'update']);
        Route::post('/profile/reset-password', [ProfileController::class, 'resetPassword']);
        Route::delete('/profile', [ProfileController::class, 'destroy']);

        // services routes
        Route::get('/services', [ServiceController::class, 'index']);
        Route::get('/services/{service}', [ServiceController::class, 'show']);

        // Bookings routes
        Route::post('/bookings', [BookingController::class, 'store']);
        Route::get('/my-bookings', [BookingController::class, 'myBookings']);
        Route::patch('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);
        Route::patch('/bookings/{booking}/reschedule', [BookingController::class, 'reschedule']);
    });
});
