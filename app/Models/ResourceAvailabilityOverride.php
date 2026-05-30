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
}
