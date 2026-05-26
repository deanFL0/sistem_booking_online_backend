<?php

namespace App\Services;

use App\Models\Resource;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class BookingAvailabilityService
{
    protected ResourceAvailabilityService $resourceAvailabilityService;

    public function __construct(ResourceAvailabilityService $resourceAvailabilityService)
    {
        $this->resourceAvailabilityService = $resourceAvailabilityService;
    }

    /**
     * Check if a given time slot is available for booking.
     */
    public function isBookingAvailable(int $serviceId, Carbon $start, Carbon $end)
    {
        // This method would contain logic to validate if the given time slot is available for the specified service and resource.

        // Get service
        $service = Service::findOrFail($serviceId);
        // Check if service is active
        if (! $service->is_active) {
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
            $availableResources = collect();

            $candidateResources = Resource::where(
                'resource_type_id', $resourceType->id
            )->where('is_active', true)
                ->get();

            foreach ($candidateResources as $resource) {
                try {
                    $this->resourceAvailabilityService
                        ->isResourceAvailable(
                            $resource->id,
                            $start,
                            $end
                        );
                    $availableResources->push($resource);
                } catch (ValidationException $e) {
                    // Resource is not available, skip to next
                    continue;
                }
            }

            // Check if we have enough available resources
            if ($availableResources->count() < $quantityNeeded) {
                throw ValidationException::withMessages([
                    'resource' => "Not enough available resources of type {$resourceType->name} for the selected time slot.",
                ]);
            }

            // Allocate resources
            $allocatedResources = $allocatedResources->merge($availableResources->take($quantityNeeded));
        }

        return $allocatedResources;
    }
}
