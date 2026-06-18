<?php

namespace App\Http\Requests;

use App\Models\Booking;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class RescheduleBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Admin bypass
        if (auth()->check() && auth()->user()->role === 'admin') {
            return true;
        }

        $token = $this->route('token');

        // Guest-management route
        if ($token) {
            $booking = Booking::where('manage_token', $token)->first();

            if (! $booking) {
                return false;
            }

            $this->merge([
                'guest_booking' => $booking,
            ]);

            // Logged-in customer accessing their own booking
            if (auth()->check()) {
                return $booking->user_id === auth()->id();
            }

            // Guest access
            return true;
        }

        // Authenticated booking route: /bookings/{booking}/...
        $booking = $this->route('booking');

        if (! $booking) {
            return false;
        }

        return auth()->check()
            && $booking->user_id === auth()->id();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'start_datetime' => 'sometimes|date',
        ];
    }
}
