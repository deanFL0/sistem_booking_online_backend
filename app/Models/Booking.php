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
}
