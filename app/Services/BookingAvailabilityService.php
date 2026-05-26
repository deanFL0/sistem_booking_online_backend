<?php

namespace App\Services;

use App\Models\Resource;
use App\Models\Service;
use Illuminate\Validation\ValidationException;
use App\Services\ResourceAvailabilityService;

class BookingAvailabilityService
{
    /**
     * Check if a given time slot is available for booking.
     *
     * @param int $serviceId
     * @param int|null $resourceId
     * @param \DateTime $start
     * @param \DateTime $end
     * @return bool
     */
    public function isBookingAvailable(int $serviceId, ?int $resourceId, \DateTime $start, \DateTime $end): bool
    {
        // This method would contain logic to validate if the given time slot is available for the specified service and resource.

        $service = Service::findOrFail($serviceId);
        $resource = Resource::findOrFail($resourceId);

        // Check if service is active
        if (!$service->is_active) {
            throw ValidationException::withMessages([
                'service' => 'The selected service is currently unavailable.',
            ]);
        }

        // Check resource availability
        $resourceAvailabilityService = new ResourceAvailabilityService();
        if (!$resourceAvailabilityService->isResourceAvailable($resourceId, $start, $end)) {
            throw ValidationException::withMessages([
                'resource' => 'Resource for the service is not available for the specified time slot.',
            ]);
        }

        return true;
    }
}