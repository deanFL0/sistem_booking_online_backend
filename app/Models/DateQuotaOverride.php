<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DateQuotaOverride extends Model
{
    use HasFactory;

    protected $fillable = [
        'override_date',
        'start_time',
        'end_time',
        'custom_quota',
        'is_closed',
    ];

    protected $casts = [
        'override_date' => 'date',
        'start_time' => 'datetime:H:i:s',
        'end_time' => 'datetime:H:i:s',
    ];
}
