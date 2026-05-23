<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'service_id',
        'resource_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'booking_date',
        'booking_time',
        'duration_minutes',
        'booking_end_time',
        'status',
        'total_price',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'booking_time' => 'datetime:H:i:s',
        'booking_end_time' => 'datetime:H:i:s',
    ];

    // Booking code generator
    protected static function booted()
    {
        static::creating(function ($booking) {
            $booking->booking_code = 'BK-'.date('Ymd').strtoupper(Str::random(6));
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
    public function resource(): BelongsTo
    {
        return $this->belongsTo(Resource::class);
    }
}
