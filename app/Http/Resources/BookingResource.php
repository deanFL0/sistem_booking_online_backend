<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_code' => $this->booking_code,
            'customer_name' => $this->customer_name,
            'customer_email' => $this->customer_email,
            'customer_phone' => $this->customer_phone,
            'start_datetime' => $this->start_datetime,
            'end_datetime' => $this->end_datetime,
            'duration_minutes' => $this->duration_minutes,
            'total_price' => $this->total_price,
            'formatted_total_price' => $this->formatted_total_price,
            'status' => $this->status,
            'completion_notified_at' => $this->completion_notified_at,
            'manage_token' => $this->manage_token,
            'has_conflict' => $this->has_conflict,
            'conflict_details' => $this->conflict_details,
        ];
    }
}
