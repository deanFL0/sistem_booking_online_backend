<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Resource;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class BookingService
{
    protected ResourceService $resourceService;

    public function __construct(ResourceService $resourceService)
    {
        $this->resourceService = $resourceService;
    }

    /**
     * Check if a given time slot is available for booking.
     */
    public function validateBooking(int $serviceId, Carbon $start, Carbon $end)
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
                    $this->resourceService
                        ->validateResourceAvailability(
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

        if ($allocatedResources->isEmpty()) {
            throw ValidationException::withMessages([
                'resource' => 'No available resources for the selected time slot.',
            ]);
        }

        return $allocatedResources;
    }

    public function validateBookingCancellation(Booking $booking)
    {
        // Dissallow cancellation if the booking is already cancelled or completed
        if (in_array($booking->status, ['cancelled', 'completed'])) {
            throw ValidationException::withMessages([
                'booking' => 'This booking cannot be cancelled.',
            ]);
        }

        // Dissallow cancellation within set number of hours before the booking start time
        $minCancellationTime = (int) setting('minimum_cancel', 24);
        if ($booking->start_datetime->diffInHours(now()) < $minCancellationTime) {
            throw ValidationException::withMessages([
                'booking' => 'This booking cannot be cancelled within ' . $minCancellationTime . ' hours of the book time.',
            ]);
        }
    }

    public function validateBookingReschedule(Booking $booking, Carbon $start, Carbon $end)
    {
        // Dissallow rescheduling if the booking is already cancelled or completed
        if (in_array($booking->status, ['cancelled', 'completed'])) {
            throw ValidationException::withMessages([
                'booking' => 'This booking cannot be rescheduled.',
            ]);
        }

        // Dissallow rescheduling within set number of hours before the booking start time
        $minRescheduleTime = (int) setting('minimum_reschedule', 24);
        if ($booking->start_datetime->diffInHours(now()) < $minRescheduleTime) {
            throw ValidationException::withMessages([
                'booking' => 'This booking cannot be rescheduled within ' . $minRescheduleTime . ' hours of the book time.',
            ]);
        }

        // Check booking availability for the new time slot
        $this->validateBooking($booking->service_id, $start, $end);
    }
}
