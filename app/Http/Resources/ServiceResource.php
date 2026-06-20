<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ServiceResource extends JsonResource
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
            'name' => $this->name,
            'description' => $this->description,
            'image_url' => $this->image_path ? asset(Storage::url($this->image_path)) : null,
            'price' => $this->price,
            'formatted_price' => $this->formatted_price,
            'pricing_type' => $this->pricing_type,
            'duration' => $this->duration,
            'total_price' => $this->total_price,
            'formatted_total_price' => $this->formatted_total_price,
            'is_active' => $this->is_active,
            'resource_types' => ResourceTypeResource::collection($this->whenLoaded('resourceTypes')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
