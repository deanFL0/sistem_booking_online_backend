<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resource extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the bookings that use this resource.
     */
    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function operationalHours()
    {
        return $this->hasMany(OperationalHour::class);
    }

    public function overrideAvailabilities()
    {
        return $this->hasMany(OverrideResourceAvailability::class);
    }
}
