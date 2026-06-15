<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Global rate limiter for API routes, with different limits for guests, regular users, and admins
        RateLimiter::for('api', function (
            Request $request
        ) {
            $user = $request->user();

            // Guest
            if (! $user) {
                return Limit::perMinute(60)
                    ->by($request->ip());
            }

            // Admin
            if ($user->is_admin) {
                return Limit::perMinute(600)
                    ->by($user->id);
            }

            // Regular authenticated user
            return Limit::perMinute(120)
                ->by($user->id);
        });

        // Rate limiter for booking-related actions
        RateLimiter::for('booking-create', function (
            Request $request
        ) {
            if ($request->user() && $request->user()->is_admin) {
                return Limit::perMinute(20)->by($request->user()->id);
            }

            if ($request->user()) {
                return [
                    Limit::perMinute(3)->by($request->user()->id),
                    Limit::perHour(10)->by($request->user()->id),
                ];
            }

            return [
                Limit::perMinute(2)->by($request->ip()),
                Limit::perHour(5)->by($request->ip()),
            ];
        });

        RateLimiter::for('booking-lookup', function (
            Request $request
        ) {
            return Limit::perMinute(60)->by(
                $request->ip()
            );
        });

        RateLimiter::for('booking-modify', function (
            Request $request
        ) {
            return [
                Limit::perMinute(5)->by(
                    $request->ip()
                ),

                Limit::perHour(20)->by(
                    $request->ip()
                ),
            ];
        });

        // Rate limiter for authentication routes (registration and login)
        RateLimiter::for('auth', function (Request $request) {

            return [
                Limit::perMinute(5)->by($request->ip()),
                Limit::perHour(20)->by($request->ip()),
            ];
        });

        // Rate limiter for public content (e.g. service listing)
        RateLimiter::for('public-content', function (
            Request $request
        ) {
            return Limit::perMinute(300)
                ->by($request->ip());
        });
    }
}
