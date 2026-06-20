<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Number;

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

    public function getTotalPriceAttribute(): int
    {
        if ($this->pricing_type === 'hourly') {
            return $this->price * ceil($this->duration / 60);
        }

        return $this->price;
    }

    public function getFormattedPriceAttribute(): string
    {
        $price = $this->price;
        $price = Number::currency($price, 'IDR', 'id', 0);
        $price = $this->pricing_type === 'hourly' ? $price . '/jam' : $price;
        return $price;
    }

    public function getFormattedTotalPriceAttribute(): string
    {
        return Number::currency($this->total_price, 'IDR', 'id', 0);
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
