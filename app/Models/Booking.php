<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'has_conflict',
        'conflict_details',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'duration_minutes' => 'integer',
        'has_conflict' => 'boolean',
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

        // Clear conflicts automatically when booking is cancelled/completed
        // or when its datetime/service changes (reschedule/update).
        static::updating(function ($booking) {
            // If status changed to cancelled or completed, clear conflict
            if ($booking->isDirty('status') && in_array($booking->status, ['cancelled', 'completed'], true)) {
                $booking->has_conflict = false;
            }

            // If start/end datetime or service_id changed (reschedule/update), clear conflict
            if ($booking->isDirty('start_datetime') || $booking->isDirty('service_id')) {
                $booking->has_conflict = false;
                // preserve existing conflict_details? we clear to indicate resolution or re-evaluation
                $booking->conflict_details = null;
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

    public function scopeMinStartDatetime($query, $minTime)
    {
        return $query->where('start_datetime', '>=', $minTime);
    }

    public function scopeMaxStartDatetime($query, $maxTime)
    {
        return $query->where('start_datetime', '<=', $maxTime);
    }

    public function scopeMaxEndDatetime($query, $maxTime)
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
}
