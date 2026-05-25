<?php

namespace App\Services;

use App\Models\Resource;
use App\Models\Service;
use Illuminate\Validation\ValidationException;

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
    public function isAvailable(int $serviceId, ?int $resourceId, \DateTime $start, \DateTime $end): bool
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

        return true;
    }
}