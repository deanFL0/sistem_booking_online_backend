<?php

namespace App\Services;

use App\Models\Resource;
use App\Models\Service;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class AvailabilityService
{
    protected ResourceService $resourceService;

    protected BookingService $bookingService;

    protected int $cacheTtl = 3600;

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
        $bookingWindowDays = (int) setting('booking_window_days', 90);
        $stepMinutes = (int) setting('slot_step_minutes', 30);
        $version = $this->getAvailabilityVersion($serviceId);
        $serviceUpdatedAt = $service->updated_at?->timestamp ?? 0;

        $cacheKey = $this->availabilityCacheKey(
            'dates',
            $serviceId,
            $startDate ?? now()->format('Y-m-d'),
            $version,
            $bookingWindowDays,
            $stepMinutes,
            $serviceUpdatedAt
        );

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($serviceId, $startDate) {
            return $this->calculateAvailableDates($serviceId, $startDate);
        });
    }

    public function getAvailableTimeSlots(int $serviceId, string $date): array
    {
        $service = Service::findOrFail($serviceId);
        $bookingWindowDays = (int) setting('booking_window_days', 90);
        $stepMinutes = (int) setting('slot_step_minutes', 30);
        $version = $this->getAvailabilityVersion($serviceId);
        $serviceUpdatedAt = $service->updated_at?->timestamp ?? 0;

        $cacheKey = $this->availabilityCacheKey(
            'slots',
            $serviceId,
            $date,
            $version,
            $bookingWindowDays,
            $stepMinutes,
            $serviceUpdatedAt
        );

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($serviceId, $date) {
            return $this->calculateAvailableTimeSlots($serviceId, $date);
        });
    }

    public function invalidateServiceAvailability(int $serviceId): void
    {
        $versionKey = $this->availabilityVersionKey($serviceId);

        if (! Cache::has($versionKey)) {
            Cache::forever($versionKey, 2);

            return;
        }

        Cache::increment($versionKey);
    }

    public function invalidateServicesAvailability(array $serviceIds): void
    {
        foreach ($serviceIds as $serviceId) {
            $this->invalidateServiceAvailability($serviceId);
        }
    }

    public function invalidateServicesByResource(Resource $resource): void
    {
        if (! $resource->relationLoaded('resourceType')) {
            $resource->load('resourceType');
        }

        $serviceIds = $resource->resourceType?->services()->pluck('services.id')->unique()->toArray() ?? [];

        if (! empty($serviceIds)) {
            $this->invalidateServicesAvailability($serviceIds);
        }
    }

    protected function calculateAvailableDates(int $serviceId, ?string $startDate = null): array
    {
        $service = Service::with('resourceTypes')->findOrFail($serviceId);

        if (! $service->is_active) {
            throw ValidationException::withMessages([
                'service' => 'The selected service is currently unavailable.',
            ]);
        }

        $bookingWindowDays = (int) setting('booking_window_days', 90);
        $stepMinutes = (int) setting('slot_step_minutes', 30);

        $periodStart = $startDate ? Carbon::parse($startDate)->startOfDay() : Carbon::now()->startOfDay();
        $periodEnd = $periodStart->copy()->addDays($bookingWindowDays - 1)->endOfDay();

        $resourceTypes = $service->resourceTypes;
        if ($resourceTypes->isEmpty()) {
            return [];
        }

        $candidateResources = $this->getCandidateResourcesForTypes($resourceTypes, $periodStart, $periodEnd);
        $availableDays = [];

        foreach (CarbonPeriod::create($periodStart, '1 day', $periodEnd) as $day) {
            if ($this->hasAnyAvailableSlotForDate($service, $day, $candidateResources, $stepMinutes)) {
                $availableDays[] = $day->format('Y-m-d');
            }
        }

        return $availableDays;
    }

    protected function calculateAvailableTimeSlots(int $serviceId, string $date): array
    {
        $service = Service::with('resourceTypes')->findOrFail($serviceId);

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

        $candidateResources = $this->getCandidateResourcesForTypes($resourceTypes, $startOfDay, $endOfDay);

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

    protected function hasAnyAvailableSlotForDate(Service $service, Carbon $date, array $candidateResources, int $stepMinutes): bool
    {
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();
        $durationMinutes = $service->duration;

        for ($slot = $startOfDay->copy(); $slot->lte($endOfDay); $slot->addMinutes($stepMinutes)) {
            $end = $slot->copy()->addMinutes($durationMinutes);
            if ($end->gt($endOfDay)) {
                break;
            }

            if ($this->isSlotAvailableForService($service, $slot, $candidateResources)) {
                return true;
            }
        }

        return false;
    }

    protected function getCandidateResourcesForTypes($resourceTypes, Carbon $rangeStart, Carbon $rangeEnd): array
    {
        $candidateResources = [];

        foreach ($resourceTypes as $resourceType) {
            $candidateResources[$resourceType->id] = Resource::where('resource_type_id', $resourceType->id)
                ->where('is_active', true)
                ->with([
                    'operationalHours',
                    'availabilityOverrides' => function ($query) use ($rangeStart, $rangeEnd) {
                        $query->where('start_time', '<', $rangeEnd)
                            ->where('end_time', '>', $rangeStart);
                    },
                    'bookings' => function ($query) use ($rangeStart, $rangeEnd) {
                        $query->whereNotIn('bookings.status', ['cancelled', 'completed'])
                            ->where('bookings.start_datetime', '<', $rangeEnd)
                            ->where('bookings.end_datetime', '>', $rangeStart);
                    },
                ])
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
                if ($this->isResourceAvailableForSlot($resource, $start, $end)) {
                    $availableResources->push($resource);
                }

                if ($availableResources->count() >= $quantityNeeded) {
                    break;
                }
            }

            if ($availableResources->count() < $quantityNeeded) {
                return false;
            }
        }

        return true;
    }

    protected function isResourceAvailableForSlot($resource, Carbon $start, Carbon $end): bool
    {
        if (! $resource->is_active) {
            return false;
        }

        $overrides = collect($resource->availabilityOverrides)->filter(function ($override) use ($start, $end) {
            return $override->start_time < $end && $override->end_time > $start;
        });

        if ($overrides->contains(function ($override) {
            return $override->status === 'unavailable';
        })) {
            return false;
        }

        $hasAvailableOverride = $overrides->contains(function ($override) {
            return $override->status === 'available';
        });

        if (! $hasAvailableOverride) {
            $operationalHour = $resource->operationalHours->firstWhere('day_of_week', $start->dayOfWeek);

            if (! $operationalHour || $operationalHour->is_closed) {
                return false;
            }

            $open = Carbon::parse($start->toDateString().' '.$operationalHour->open_time->format('H:i:s'));
            $close = Carbon::parse($start->toDateString().' '.$operationalHour->close_time->format('H:i:s'));

            if ($start->lt($open) || $end->gt($close)) {
                return false;
            }
        }

        $hasBookingConflict = collect($resource->bookings)->contains(function ($booking) use ($start, $end) {
            return $booking->start_datetime < $end && $booking->end_datetime > $start;
        });

        return ! $hasBookingConflict;
    }

    protected function availabilityVersionKey(int $serviceId): string
    {
        return "availability_service_version:{$serviceId}";
    }

    protected function availabilityCacheKey(string $type, int $serviceId, string $identifier, int $version, int $bookingWindowDays, int $stepMinutes, int $serviceUpdatedAt): string
    {
        return sprintf(
            'availability:%s:service:%d:%s:bw:%d:step:%d:svc_updated:%d:v:%d',
            $type,
            $serviceId,
            $identifier,
            $bookingWindowDays,
            $stepMinutes,
            $serviceUpdatedAt,
            $version
        );
    }

    protected function getAvailabilityVersion(int $serviceId): int
    {
        return (int) Cache::get($this->availabilityVersionKey($serviceId), 1);
    }
}
