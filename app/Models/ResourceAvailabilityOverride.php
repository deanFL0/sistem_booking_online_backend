<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResourceAvailabilityOverride extends Model
{
    use HasFactory;

    protected $table = 'resource_availability_overrides';

    protected $fillable = [
        'resource_id',
        'start_datetime',
        'end_datetime',
        'status',
        'reason',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
    ];

    public function resource()
    {
        return $this->belongsTo(Resource::class);
    }

    public function scopeMinStartDateTime($query, $minTime)
    {
        return $query->where('start_datetime', '>=', $minTime);
    }

    public function scopeMaxStartDateTime($query, $maxTime)
    {
        return $query->where('start_datetime', '<=', $maxTime);
    }

    public function scopeMinEndDateTime($query, $minTime)
    {
        return $query->where('end_datetime', '>=', $minTime);
    }

    public function scopeMaxEndDateTime($query, $maxTime)
    {
        return $query->where('end_datetime', '<=', $maxTime);
    }

    public function scopeOnDay($query, $date)
    {
        return $query->where('start_datetime', '<', Carbon::parse($date)->endOfDay())
            ->where('end_datetime', '>', Carbon::parse($date)->startOfDay());
    }
}
