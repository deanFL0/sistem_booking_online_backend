<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'service_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'start_datetime',
        'end_datetime',
        'duration_minutes',
        'total_price',
        'status',
        'completion_notified_at',
        'manage_token',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'duration_minutes' => 'integer',
    ];

    protected static function booted()
    {
        // Booking code generator
        static::creating(function ($booking) {
            $booking->booking_code = 'BK-'.date('Ymd').strtoupper(Str::random(6));

            // Generate token for guest users to manage their bookings
            if (! $booking->user_id) {
                $booking->manage_token = Str::uuid();
            }
        });
    }

    // get active booking
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['cancelled', 'completed', 'no_show']);
    }

    /**
     * Get the service that belongs to the booking.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the registered user who made this booking.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the resource (barber, chair, etc.) assigned to this booking.
     */
    public function resources(): BelongsToMany
    {
        return $this->belongsToMany(Resource::class);
    }

    public function scopeMinStartTime($query, $minTime)
    {
        return $query->where('start_datetime', '>=', $minTime);
    }

    public function scopeMaxEndTime($query, $maxTime)
    {
        return $query->where('end_datetime', '<=', $maxTime);
    }

    public function scopeMinDuration($query, $minDuration)
    {
        return $query->where('duration_minutes', '>=', $minDuration);
    }

    public function scopeMaxDuration($query, $maxDuration)
    {
        return $query->where('duration_minutes', '<=', $maxDuration);
    }

    public function scopeMinPrice($query, $minPrice)
    {
        return $query->where('total_price', '>=', $minPrice);
    }

    public function scopeMaxPrice($query, $maxPrice)
    {
        return $query->where('total_price', '<=', $maxPrice);
    }

    // get count of booking for each status in a given date range
    public static function stats(string $range = 'month'): array
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

        $rows = static::query()
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

        return [
            'range' => $range,
            'data' => $data,
        ];
    }
}
