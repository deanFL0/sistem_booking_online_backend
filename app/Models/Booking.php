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
}
