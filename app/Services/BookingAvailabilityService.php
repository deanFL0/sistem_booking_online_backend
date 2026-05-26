<?php

namespace App\Services;

use App\Models\Resource;
use App\Models\Service;
use Illuminate\Validation\ValidationException;
use App\Services\ResourceAvailabilityService;

class BookingAvailabilityService
{
    protected ResourceAvailabilityService $resourceAvailabilityService;

    public function __construct(ResourceAvailabilityService $resourceAvailabilityService)
    {
        $this->resourceAvailabilityService = $resourceAvailabilityService;
    }

    /**
     * Check if a given time slot is available for booking.
     *
     * @param int $serviceId
     * @param \Carbon\Carbon $start
     * @param \Carbon\Carbon $end
     * @return bool
     */
    public function isBookingAvailable(int $serviceId, \Carbon\Carbon $start, \Carbon\Carbon $end): bool
    {
        // This method would contain logic to validate if the given time slot is available for the specified service and resource.

        // Get service
        $service = Service::findOrFail($serviceId);
        // Check if service is active
        if (!$service->is_active) {
            throw ValidationException::withMessages([
                'service' => 'The selected service is currently unavailable.',
            ]);
        }

        // Check resource availability
        // Get required resource types for the service
        $requiredResourceTypes = $service->resourceTypes;
        if ($requiredResourceTypes->isEmpty()) {
            throw ValidationException::withMessages([
                'service' => 'No resources requirement configured for the selected service.',
            ]);
        }

        $allocatedResources = collect();

        foreach ($requiredResourceTypes as $resourceType) {
            $quantityNeeded = $resourceType->pivot->quantity;

            // Get available resources of this type for the given time slot
            $availableResources = Resource::where('resource_type_id', $resourceType->id)
                ->where('is_active', true)
                ->get()
                ->filter(function ($resource) use ($start, $end) {
                    return $this->resourceAvailabilityService->isResourceAvailable($resource->id, $start, $end);
                });

            // Check if we have enough available resources
            if ($availableResources->count() < $quantityNeeded) {
                throw ValidationException::withMessages([
                    'resource' => "Not enough available resources of type {$resourceType->name} for the selected time slot.",
                ]);
            }

            // Allocate resources
            $allocatedResources = $allocatedResources->merge($availableResources->take($quantityNeeded));
        }

        return true;
    }
}