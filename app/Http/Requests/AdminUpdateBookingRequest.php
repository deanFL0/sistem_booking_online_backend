<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AdminUpdateBookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Check if the user is an admin
        return auth()->user()->role === 'admin';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => 'nullable|exists:users,id',
            'service_id' => 'sometimes|exists:services,id',
            'customer_name' => 'sometimes|string|max:255',
            'customer_email' => 'sometimes|email|max:255',
            'customer_phone' => 'sometimes|nullable|string|max:20',
            'start_datetime' => 'sometimes|date',
            'status' => 'sometimes|in:pending,confirmed,ongoing,cancelled,completed,no_show',
            'has_conflict' => 'sometimes|boolean',
            'conflict_details' => 'sometimes|nullable|string',
        ];
    }
}
