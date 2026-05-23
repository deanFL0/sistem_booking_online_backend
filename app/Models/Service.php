<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'pricing_type',
        'duration',
        'is_active',
    ];

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function operationalHours()
    {
        return $this->hasMany(OperationalHour::class);
    }

    public function hourlyQuotas()
    {
        return $this->hasMany(HourlyQuota::class);
    }

    public function dateQuotaOverrides()
    {
        return $this->hasMany(DateQuotaOverride::class);
    }
}
