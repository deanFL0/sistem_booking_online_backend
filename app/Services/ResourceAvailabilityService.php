<?php

namespace App\Services;

use App\Models\Resource;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class ResourceAvailabilityService
{
    /**
     * Check if resource is available for the given time slot.
     *
     * @param int $resourceId
     * @param \Carbon\Carbon $start
     * @param \Carbon\Carbon $end
     * @return bool
     */
    public function isResourceAvailable(
        int $resourceId,
        Carbon $start,
        Carbon $end
    ): bool {

        $resource = Resource::findOrFail($resourceId);

        /*
        |--------------------------------------------------------------------------
        | Check global availability
        |--------------------------------------------------------------------------
        */

        if (! $resource->is_active) {
            throw ValidationException::withMessages([
                'resource' => [
                    'The selected resource is unavailable.'
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Check availability overrides
        |--------------------------------------------------------------------------
        |
        | Overlap formula:
        |
        | existing_start < requested_end
        | AND
        | existing_end > requested_start
        |
        */

        $overrides = $resource
            ->availabilityOverrides()
            ->where('start_datetime', '<', $end)
            ->where('end_datetime', '>', $start)
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Unavailable override takes precedence
        |--------------------------------------------------------------------------
        */

        $hasUnavailableOverride = $overrides
            ->contains(function ($override) {
                return $override->status === 'unavailable';
            });

        if ($hasUnavailableOverride) {
            throw ValidationException::withMessages([
                'resource' => [
                    'The selected resource is unavailable during this time slot.'
                ],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Available override bypasses operational hours
        |--------------------------------------------------------------------------
        */

        $hasAvailableOverride = $overrides
            ->contains(function ($override) {
                return $override->status === 'available';
            });

        /*
        |--------------------------------------------------------------------------
        | Check operational hours
        |--------------------------------------------------------------------------
        */

        if (! $hasAvailableOverride) {

            $operationalHour = $resource
                ->operationalHours()
                ->where(
                    'day_of_week',
                    $start->dayOfWeek
                )
                ->first();

            if (! $operationalHour) {
                throw ValidationException::withMessages([
                    'resource' => [
                        'The resource is unavailable on this day.'
                    ],
                ]);
            }

            if ($operationalHour->is_closed) {
                throw ValidationException::withMessages([
                    'resource' => [
                        'The resource is unavailable on this day.'
                    ],
                ]);
            }

            $open = Carbon::parse(
                $start->toDateString() . ' ' .
                $operationalHour->open_time
            );

            $close = Carbon::parse(
                $start->toDateString() . ' ' .
                $operationalHour->close_time
            );

            /*
            |--------------------------------------------------------------------------
            | Entire booking must fit operational hours
            |--------------------------------------------------------------------------
            */

            if (
                $start < $open ||
                $end > $close
            ) {
                throw ValidationException::withMessages([
                    'resource' => [
                        'The requested time slot is outside operational hours.'
                    ],
                ]);
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Check booking conflicts
        |--------------------------------------------------------------------------
        */

        $hasBookingConflict = $resource
            ->bookings()
            ->whereNotIn('status', [
                'cancelled',
                'completed',
            ])
            ->where('start_datetime', '<', $end)
            ->where('end_datetime', '>', $start)
            ->exists();

        if ($hasBookingConflict) {
            throw ValidationException::withMessages([
                'resource' => [
                    'The selected resource is already booked during this time slot.'
                ],
            ]);
        }

        return true;
    }
}