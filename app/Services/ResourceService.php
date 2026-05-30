<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Resource;
use App\Models\ResourceAvailabilityOverride;
use App\Models\User;
use App\Notifications\ResourceOverrideConflictNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class ResourceService
{
    /**
     * Check if resource is available for the given time slot.
     *
     * @return void
     *
     * @throws ValidationException
     */
    public function validateResourceAvailability(
        int $resourceId,
        Carbon $start,
        Carbon $end
    ) {

        $resource = Resource::findOrFail($resourceId);

        /*
        |--------------------------------------------------------------------------
        | Check global availability
        |--------------------------------------------------------------------------
        */

        if (! $resource->is_active) {
            throw ValidationException::withMessages([
                'resource' => [
                    'The selected resource is unavailable.',
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
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
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
                    'The selected resource is unavailable during this time slot.',
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
                        'The resource is unavailable on this day.',
                    ],
                ]);
            }

            if ($operationalHour->is_closed) {
                throw ValidationException::withMessages([
                    'resource' => [
                        'The resource is unavailable on this day.',
                    ],
                ]);
            }

            $open = Carbon::parse(
                $start->toDateString().' '.
                $operationalHour->open_time->format('H:i:s')
            );

            $close = Carbon::parse(
                $start->toDateString().' '.
                $operationalHour->close_time->format('H:i:s')
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
                        'The requested time slot is outside operational hours.',
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
            ->whereNotIn('bookings.status', [
                'cancelled',
                'completed',
            ])
            ->where('bookings.start_datetime', '<', $end)
            ->where('bookings.end_datetime', '>', $start)
            ->exists();

        if ($hasBookingConflict) {
            throw ValidationException::withMessages([
                'resource' => [
                    'The selected resource is already booked during this time slot.',
                ],
            ]);
        }
    }

    /**
     * Process a resource availability override by marking affected bookings with conflicts and notifying admins.
     *
     * @throws ValidationException
     */
    public function processOverride(
        ResourceAvailabilityOverride $override
    ): void {
        // Skip if override makes resource available
        if ($override->status === 'available') {
            return;
        }

        $overrideStart = Carbon::parse($override->start_time);

        $overrideEnd = Carbon::parse($override->end_time);

        // find affected bookings
        $affectedBookings = Booking::whereHas(
            'resources',
            function ($query) use ($override) {
                $query->where(
                    'resources.id',
                    $override->resource_id
                );
            }
        )
            ->whereNotIn('status', ['cancelled', 'completed'])
            ->where('start_datetime', '<', $overrideEnd)
            ->where('end_datetime', '>', $overrideStart)
            ->get();

        // get admins to notify
        $admins = User::where('role', 'admin')->get();

        // mark conflict
        foreach ($affectedBookings as $booking) {
            $newConflictDetails =
                'Resource '
                .$override->resource->name
                .' unavailable due to override from '
                .Carbon::parse($override->start_time)->format('Y-m-d H:i:s')
                .' to '
                .Carbon::parse($override->end_time)->format('Y-m-d H:i:s');

            if (! $booking->has_conflict) {
                $booking->has_conflict = true;
                $booking->conflict_details = $newConflictDetails;
            } else {
                // Prevent duplicate conflict messages
                if (! str_contains($booking->conflict_details, $newConflictDetails)) {
                    $booking->conflict_details .= "\n\n".$newConflictDetails;
                }
            }
            $booking->save();

            // Notify admins
            Notification::send(
                $admins,
                new ResourceOverrideConflictNotification(
                    $booking,
                    $override
                )
            );
        }
    }
}
