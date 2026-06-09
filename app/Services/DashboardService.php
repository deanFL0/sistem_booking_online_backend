<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\ResourceAvailabilityOverride;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * Get booking statistics for a given range (week, month, year).
     *
     * @param  string  $range  The range for the statistics (week, month, year)
     */
    private function getBookingStatsByRange(string $range = 'month'): array
    {
        $now = Carbon::now();

        switch ($range) {
            case 'week':
                $start = $now->copy()->startOfWeek();
                $end = $now->copy()->endOfWeek();
                $dateFormat = 'YYYY-MM-DD';
                $periodFormat = 'Y-m-d';
                $periodInterval = '1 day';
                break;

            case 'year':
                $start = $now->copy()->startOfYear();
                $end = $now->copy()->endOfYear();
                $dateFormat = 'YYYY-MM';
                $periodFormat = 'Y-m';
                $periodInterval = '1 month';
                break;

            case 'month':
            default:
                $start = $now->copy()->startOfMonth();
                $end = $now->copy()->endOfMonth();
                $dateFormat = 'YYYY-MM-DD';
                $periodFormat = 'Y-m-d';
                $periodInterval = '1 day';
                $range = 'month';
                break;
        }

        $rows = Booking::query()
            ->select(
                DB::raw("TO_CHAR(start_datetime, '{$dateFormat}') as period"),
                DB::raw("COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending"),
                DB::raw("COUNT(CASE WHEN status = 'confirmed' THEN 1 END) as confirmed"),
                DB::raw("COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled"),
                DB::raw("COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed"),
                DB::raw("COUNT(CASE WHEN status = 'no_show' THEN 1 END) as no_show")
            )
            ->whereBetween('start_datetime', [$start, $end])
            ->groupBy(DB::raw("TO_CHAR(start_datetime, '{$dateFormat}')"))
            ->orderBy(DB::raw("TO_CHAR(start_datetime, '{$dateFormat}')"))
            ->get()
            ->keyBy('period');

        $period = CarbonPeriod::create($start, $periodInterval, $end);

        $data = [];

        foreach ($period as $date) {
            $key = $date->format($periodFormat);
            $row = $rows->get($key);

            $data[] = [
                'date' => $key,
                'pending' => $row ? (int) $row->pending : 0,
                'confirmed' => $row ? (int) $row->confirmed : 0,
                'cancelled' => $row ? (int) $row->cancelled : 0,
                'completed' => $row ? (int) $row->completed : 0,
                'no_show' => $row ? (int) $row->no_show : 0,
            ];
        }

        return $data;
    }

    /**
     * Get booking statistics for the dashboard.
     */
    public function getBookingStats(): array
    {
        return [
            'week' => $this->getBookingStatsByRange('week'),
            'month' => $this->getBookingStatsByRange('month'),
            'year' => $this->getBookingStatsByRange('year'),
        ];
    }

    /**
     * Get popular services based on booking count.
     *
     * @param  int  $limit  The number of popular services to return
     * @param  int  $days  The number of days to consider
     */
    public function getPopularServices(int $limit = 5, int $days = 30): array
    {
        return Booking::query()
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subDays($days))
            ->select('service_id', DB::raw('COUNT(*) as total_bookings'))
            ->groupBy('service_id')
            ->orderByDesc('total_bookings')
            ->limit($limit)
            ->with('service:id,name')
            ->get()
            ->map(function ($item) {
                return [
                    'service_id' => $item->service_id,
                    'service_name' => $item->service?->name,
                    'total_bookings' => (int) $item->total_bookings,
                ];
            })
            ->toArray();
    }

    /**
     * Get resource availability overrides for today and tomorrow.
     */
    public function getUpcomingResourceOverrides(): array
    {
        $today = Carbon::today();
        $tomorrow = Carbon::tomorrow();
        $end = $tomorrow->copy()->endOfDay();

        return ResourceAvailabilityOverride::query()
            ->with('resource:id,name')
            ->where('start_time', '<=', $end)
            ->where('end_time', '>=', $today)
            ->orderBy('start_time')
            ->get()
            ->map(function ($item) {
                $start = Carbon::parse($item->start_time);
                $end = Carbon::parse($item->end_time);
                $now = Carbon::now();

                // Day label
                if ($start->isToday()) {
                    $dayLabel = 'Today';
                } elseif ($start->isTomorrow()) {
                    $dayLabel = 'Tomorrow';
                } else {
                    $dayLabel = $start->format('M j');
                }

                // Time formatting
                if ($start->isSameDay($end)) {
                    $displayTime =
                        $dayLabel.
                        ' • '.
                        $start->format('H:i').
                        ' - '.
                        $end->format('H:i');
                } else {
                    $displayTime =
                        $start->format('M j H:i').
                        ' → '.
                        $end->format('M j H:i');
                }

                return [
                    'id' => $item->id,
                    'resource_id' => $item->resource_id,
                    'resource_name' => $item->resource?->name,
                    'status' => $item->status,
                    'display_time' => $displayTime,
                    'is_ongoing' => $now->between(
                        $start,
                        $end
                    ),
                    'reason' => $item->reason,
                ];
            })
            ->toArray();
    }
}
