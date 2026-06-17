<?php

namespace App\Services;

use App\Models\Resource;
use App\Models\Service;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Validation\ValidationException;

class AvailabilityService
{
    protected ResourceService $resourceService;

    protected BookingService $bookingService;

    public function __construct(
        ResourceService $resourceService,
        BookingService $bookingService
    ) {
        $this->resourceService = $resourceService;
        $this->bookingService = $bookingService;
    }

    public function getAvailableDates(int $serviceId, ?string $startDate = null): array
    {
        $service = Service::findOrFail($serviceId);

        if (! $service->is_active) {
            throw ValidationException::withMessages([
                'service' => 'The selected service is currently unavailable.',
            ]);
        }

        $bookingWindowDays = (int) setting('booking_window_days', 90);
        $stepMinutes = (int) setting('slot_step_minutes', 30);

        $periodStart = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::now()->startOfDay();
        $periodEnd = $periodStart->copy()->addDays($bookingWindowDays - 1)->endOfDay();

        $availableDays = [];

        foreach (CarbonPeriod::create($periodStart, '1 day', $periodEnd) as $day) {
            if ($this->hasAvailableSlotForDate($service, $day, $stepMinutes)) {
                $availableDays[] = $day->format('Y-m-d');
            }
        }

        return $availableDays;
    }

    public function getAvailableTimeSlots(int $serviceId, string $date): array
    {
        $service = Service::findOrFail($serviceId);

        if (! $service->is_active) {
            throw ValidationException::withMessages([
                'service' => 'The selected service is currently unavailable.',
            ]);
        }

        $bookingWindowDays = (int) setting('booking_window_days', 90);
        $stepMinutes = (int) setting('slot_step_minutes', 30);

        $startOfDay = Carbon::parse($date)->startOfDay();
        $endOfDay = Carbon::parse($date)->endOfDay();
        $maxDate = Carbon::now()->startOfDay()->addDays($bookingWindowDays - 1)->endOfDay();

        if ($startOfDay->gt($maxDate)) {
            return [];
        }

        $durationMinutes = $service->duration;
        $availableSlots = [];

        $resourceTypes = $service->resourceTypes;
        if ($resourceTypes->isEmpty()) {
            return [];
        }

        $candidateResources = $this->getCandidateResourcesForTypes($resourceTypes);

        for ($slot = $startOfDay->copy(); $slot->lte($endOfDay); $slot->addMinutes($stepMinutes)) {
            $end = $slot->copy()->addMinutes($durationMinutes);
            if ($end->gt($endOfDay)) {
                break;
            }

            if ($this->isSlotAvailableForService($service, $slot, $candidateResources)) {
                $availableSlots[] = $slot->format('H:i');
            }
        }

        return $availableSlots;
    }

    protected function hasAvailableSlotForDate(Service $service, Carbon $date, int $stepMinutes): bool
    {
        return ! empty($this->getAvailableTimeSlots(
            $service->id,
            $date->format('Y-m-d')
        ));
    }

    protected function getCandidateResourcesForTypes($resourceTypes): array
    {
        $candidateResources = [];

        foreach ($resourceTypes as $resourceType) {
            $candidateResources[$resourceType->id] = Resource::where('resource_type_id', $resourceType->id)
                ->where('is_active', true)
                ->get();
        }

        return $candidateResources;
    }

    protected function isSlotAvailableForService(Service $service, Carbon $start, array $candidateResources): bool
    {
        $end = $this->bookingService->calculateEndDatetime($service->id, $start);
        $requiredResourceTypes = $service->resourceTypes;

        foreach ($requiredResourceTypes as $resourceType) {
            $quantityNeeded = $resourceType->pivot->quantity;
            $availableResources = collect();

            foreach ($candidateResources[$resourceType->id] as $resource) {
                try {
                    $this->resourceService->validateResourceAvailability($resource->id, $start, $end);
                    $availableResources->push($resource);

                    if ($availableResources->count() >= $quantityNeeded) {
                        break;
                    }
                } catch (ValidationException $e) {
                    continue;
                }
            }

            if ($availableResources->count() < $quantityNeeded) {
                return false;
            }
        }

        return true;
    }
}
