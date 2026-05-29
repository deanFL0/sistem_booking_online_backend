<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Resource;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class BookingService
{
    protected ResourceService $resourceService;

    public function __construct(ResourceService $resourceService)
    {
        $this->resourceService = $resourceService;
    }

    /**
     * Get end datetime for a booking based on service duration.
     */
    public function calculateEndDatetime(int $serviceId, Carbon $start): Carbon
    {
        $service = Service::findOrFail($serviceId);
        $duration = $service->duration;

        // calculate end datetime based on start datetime and service duration
        return Carbon::parse($start)->addMinutes($duration);
    }

    /**
     * Calculate total price for a booking based on service pricing type and duration.
     *
     * @throws ValidationException
     */
    public function calculateTotalPrice(int $serviceId): float
    {
        $service = Service::findOrFail($serviceId);

        if ($service->pricing_type === 'one_time') {
            return $service->price;
        }
        if ($service->pricing_type === 'hourly') {
            // For hourly pricing, we will calculate the total price based on the duration the service
            return $service->price * $service->duration / 60;
        }

        throw ValidationException::withMessages([
            'service' => 'Invalid pricing type for the selected service.',
        ]);
    }

    /**
     * Check if user/email have reached booking limit
     *
     * @throws ValidationException
     */
    public function ensureBookingLimit(array $data): void
    {
        // Authenticated user
        if (auth()->check()) {
            $count = Booking::active()
                ->where('user_id', auth()->id())
                ->count();

            if ($count >= 5) {
                throw ValidationException::withMessages([
                    'booking' => ['Maximum active bookings reached.'],
                ]);
            }

            return;
        }

        // Guest
        $count = Booking::active()
            ->where('customer_email', $data['customer_email'])
            ->count();

        if ($count >= 1) {
            throw ValidationException::withMessages([
                'booking' => ['Maximum active bookings reached.'],
            ]);
        }
    }

    /**
     * Get available resources for a given service and time slot, and validate the booking.
     *
     * @return Collection<\App\Models\Resource>
     *
     * @throws ValidationException
     */
    public function getBookingResources(int $serviceId, Carbon $start)
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

        // Calculate end datetime based on service duration
        $end = $this->calculateEndDatetime($serviceId, $start);

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
        $minRescheduleTime = (int) setting('min_reschedule_hours', 24);
        $hoursUntilBooking = now()->diffInHours($booking->start_datetime, false);
        if ($hoursUntilBooking < $minRescheduleTime) {
            throw ValidationException::withMessages([
                'booking' => 'This booking cannot be cancelled within '
                    .$minRescheduleTime
                    .' hours of the booking time.',
            ]);
        }
    }

    /**
     * Validate Booking for rescheduling
     *
     * @return void
     *
     * @throws ValidationException
     */
    public function validateBookingReschedule(Booking $booking, Carbon $start)
    {
        // Calculate end datetime based on service duration
        $end = $this->calculateEndDatetime($booking->service_id, $start);

        // Dissallow rescheduling if the booking is already cancelled or completed
        if (in_array($booking->status, ['cancelled', 'completed'])) {
            throw ValidationException::withMessages([
                'booking' => 'This booking cannot be rescheduled.',
            ]);
        }

        // Dissallow rescheduling within set number of hours before the booking start time
        $minRescheduleTime = (int) setting('min_reschedule_hours', 24);
        $hoursUntilBooking = now()->diffInHours($booking->start_datetime, false);
        if ($hoursUntilBooking < $minRescheduleTime) {
            throw ValidationException::withMessages([
                'booking' => 'This booking cannot be rescheduled within '
                    .$minRescheduleTime
                    .' hours of the booking time.',
            ]);
        }

        // Check booking availability for the new time slot
        $this->getBookingResources($booking->service_id, $start);
    }
}
