<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResourceType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    public function services()
    {
        return $this->belongsToMany(
            Service::class
        )->withPivot('quantity');
    }

    public function resources()
    {
        return $this->hasMany(Resource::class, 'resource_type_id');
    }
}
