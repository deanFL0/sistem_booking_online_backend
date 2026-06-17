<?php

namespace App\Notifications;

use App\Models\Booking;
use App\Models\ResourceAvailabilityOverride;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ResourceOverrideConflictNotification extends Notification
{
    use Queueable;

    public function __construct(public Booking $booking, public ResourceAvailabilityOverride $override) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'booking_id' => $this->booking->id,
            'booking_code' => $this->booking->booking_code,
            'override_id' => $this->override->id,
            'message' => 'Booking '
                .$this->booking->booking_code
                .' conflicts with resource override.',

            'resource_id' => $this->override->resource_id,
            'start_datetime' => $this->override->start_datetime,
            'end_datetime' => $this->override->end_datetime,
        ];
    }
}
