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
        'start_time',
        'end_time',
        'status',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function resource()
    {
        return $this->belongsTo(Resource::class);
    }

    public function scopeMinTime($query, $minTime)
    {
        return $query->where('start_time', '>=', $minTime);
    }

    public function scopeMaxTime($query, $maxTime)
    {
        return $query->where('end_time', '<=', $maxTime);
    }

    public function scopeOnDay($query, $date)
    {
        return $query->where('start_time', '<', Carbon::parse($date)->endOfDay())
            ->where('end_time', '>', Carbon::parse($date)->startOfDay());
    }
}
