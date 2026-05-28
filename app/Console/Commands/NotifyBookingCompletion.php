<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\User;
use App\Notifications\BookingNeedsCompletionNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class NotifyBookingCompletion extends Command
{
    protected $signature = 'bookings:notify-completion';

    public function handle()
    {
        $bookings = Booking::whereIn('status', ['confirmed', 'ongoing'])
            ->where('end_datetime', '<=', now())
            ->whereNull('completion_notified_at')
            ->get();

        $admins = User::where('role', '=', 'admin')->get();

        foreach ($bookings as $booking) {
            Notification::send(
                $admins,
                new BookingNeedsCompletionNotification($booking)
            );

            $booking->update([
                'completion_notified_at' => now(),
            ]);
        }
    }
}
