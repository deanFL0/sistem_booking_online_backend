<?php

namespace App\Services;

use App\Models\Booking;
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
}
