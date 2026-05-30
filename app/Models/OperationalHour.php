<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperationalHour extends Model
{
    use HasFactory;

    protected $fillable = [
        'resource_id',
        'day_of_week',
        'open_time',
        'close_time',
        'is_closed',
    ];

    protected $casts = [
        'open_time' => 'datetime:H:i',
        'close_time' => 'datetime:H:i',
        'is_closed' => 'boolean',
    ];

    protected $appends = ['day_name'];

    public function getDayNameAttribute()
    {
        return Carbon::create()->locale('id')->day($this->day_of_week)->dayName;
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function resource()
    {
        return $this->belongsTo(Resource::class);
    }

    public function scopeMinTime($query, $minTime)
    {
        return $query->where('open_time', '>=', $minTime);
    }

    public function scopeMaxTime($query, $maxTime)
    {
        return $query->where('close_time', '<=', $maxTime);
    }
}
