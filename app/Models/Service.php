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
        'image_path',
        'price',
        'pricing_type',
        'duration',
        'is_active',
    ];

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function resourceTypes()
    {
        return $this->belongsToMany(ResourceType::class)->withPivot('quantity');
    }

    public function scopeMaxPrice($query, $price)
    {
        return $query->where('price', '<=', $price);
    }

    public function scopeMinPrice($query, $price)
    {
        return $query->where('price', '>=', $price);
    }

    public function scopeMaxDuration($query, $duration)
    {
        return $query->where('duration', '<=', $duration);
    }

    public function scopeMinDuration($query, $duration)
    {
        return $query->where('duration', '>=', $duration);
    }

    public function getTotalPriceAttribute(): int
    {
        if ($this->pricing_type === 'hourly') {
            return $this->price * ceil($this->duration / 60);
        }

        return $this->price;
    }

    public function scopeMaxTotalPrice($query, $price)
    {
        return $query->whereRaw("
            CASE
                WHEN pricing_type = 'hourly'
                THEN price * CEIL(duration / 60.0)
                ELSE price
            END <= ?
        ", [$price]);
    }

    public function scopeMinTotalPrice($query, $price)
    {
        return $query->whereRaw("
            CASE
                WHEN pricing_type = 'hourly'
                THEN price * CEIL(duration / 60.0)
                ELSE price
            END >= ?
        ", [$price]);
    }
}
