<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResourceAvailabilityOverride extends Model
{
    use HasFactory;

    protected $table = 'resource_availability_overrides';

    protected $fillable = [
        'resource_id',
        'date',
        'start_time',
        'end_time',
        'status',
    ];

    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime:H:i:s',
        'end_time' => 'datetime:H:i:s',
    ];

    public function resource()
    {
        return $this->belongsTo(Resource::class);
    }
}
