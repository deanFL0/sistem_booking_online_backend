<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;

class DashboardController extends Controller
{
    public function index(DashboardService $dashboardService)
    {
        return response()->json([
            'data' => [
                'booking_stats' => $dashboardService->getBookingStats(),
                'popular_services' => $dashboardService->getPopularServices(),
                'resource_availability_overrides' => $dashboardService->getUpcomingResourceOverrides(),
                'conflicted_bookings' => $dashboardService->getConflictedBookings(),
            ],
        ]);
    }
}
