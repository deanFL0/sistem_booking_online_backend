<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OperationalHour extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'day_of_week',
        'open_time',
        'close_time',
        'is_closed',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
