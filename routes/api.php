<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\OperationalHourController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ResourceAvailabilityOverrideController;
use App\Http\Controllers\Api\ResourceController;
use App\Http\Controllers\Api\ResourceTypeController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\ServiceResourceTypeController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public routes (services and booking creation for everyone)
    // service routes
    Route::get('/services', [ServiceController::class, 'index'])->middleware('throttle:public-content');
    Route::get('/services/{service}', [ServiceController::class, 'show'])->middleware('throttle:public-content');
    // booking routes
    Route::post('/bookings', [BookingController::class, 'store'])->middleware('throttle:booking-create');
    Route::get('/guest-bookings/{token}', [BookingController::class, 'guestShow'])->whereUuid('token')->middleware('throttle:booking-lookup');
    Route::post('/guest-bookings/{token}/cancel', [BookingController::class, 'guestCancel'])->whereUuid('token')->middleware('throttle:booking-modify');
    Route::patch('/guest-bookings/{token}/reschedule', [BookingController::class, 'guestReschedule'])->whereUuid('token')->middleware('throttle:booking-modify');

    // auth routes (guest only)
    Route::middleware(['throttle:auth'])->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
    });

    // routes for admin
    Route::middleware(['auth:sanctum', 'is_admin'])->group(function () {
        // user routes
        Route::apiResource('/users', UserController::class)->withTrashed();
        Route::post('/users/{user}/restore', [UserController::class, 'restore']);

        // service routes
        Route::apiResource('/services', ServiceController::class)
            ->except(['index', 'show']);

        // resource routes
        Route::apiResource('/resources', ResourceController::class);

        // resource type routes
        Route::apiResource('/resource-types', ResourceTypeController::class);

        // service resource types routes
        Route::apiResource('/services/{service}/resource-types', ServiceResourceTypeController::class)
            ->only(['index', 'store', 'update', 'destroy']);

        // operational hour routes
        Route::scopeBindings()->group(function () {
            Route::apiResource('resources.operational-hours', OperationalHourController::class)
                ->parameters([
                    'operational-hours' => 'operationalHour',
                ]);
        });

        // resource availability override routes
        Route::scopeBindings()->group(function () {
            Route::apiResource('resources.availability-overrides', ResourceAvailabilityOverrideController::class)
                ->parameters([
                    'availability-overrides' => 'resourceAvailabilityOverride',
                ]);
        });

        // bookings routes
        Route::apiResource('/bookings', BookingController::class)
            ->except(['store']);

        // dashboard route
        Route::get('/dashboard', [DashboardController::class, 'index']);

        // options routes
        Route::prefix('options')->group(function () {
            Route::get('/services', [ServiceController::class, 'options']);
            Route::get('/resource-types', [ResourceTypeController::class, 'options']);
        });
    });

    // routes for authenticated users
    Route::middleware('auth:sanctum')->group(function () {
        // profile routes
        Route::get('/profile', [ProfileController::class, 'me']);
        Route::put('/profile', [ProfileController::class, 'update']);
        Route::patch('/profile', [ProfileController::class, 'update']);
        Route::post('/profile/reset-password', [ProfileController::class, 'resetPassword']);
        Route::delete('/profile', [ProfileController::class, 'destroy']);

        // Bookings routes (user-specific)
        Route::get('/my-bookings', [BookingController::class, 'myBookings']);
        Route::get('/my-bookings/{booking}', [BookingController::class, 'show']);
        Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);
        Route::patch('/bookings/{booking}/reschedule', [BookingController::class, 'reschedule']);

        // Notification Routes
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);

        // Logout route
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});
