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
        $token = $this->route('token');

        $booking = Booking::where('manage_token', $token)->first();

        if (! $booking) {
            return false;
        }

        // Store for later if needed
        $this->merge([
            'guest_booking' => $booking,
        ]);

        // Authenticated owner
        if (auth()->check()) {
            return $booking->user_id === auth()->id();
        }

        // Guest access via token
        return true;
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
