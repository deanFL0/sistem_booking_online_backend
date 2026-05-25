<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreOperationalHourRequiest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // check if the user is admin
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
            'day_of_week'=>'required|integer|min:0|max:6',
            'open_time'=>'required|date_format:H:i',
            'close_time'=>'required|date_format:H:i|after:open_time',
            'is_closed'=>'boolean',
        ];
    }
}
