<?php

namespace App\Http\Requests\Availability;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAvailabilityRequest extends FormRequest
{
    public function authorize(): bool
{
    return (bool) $this->user();
}


    public function rules(): array
    {
        return [
            'day_of_week' => ['sometimes', 'required', 'integer', 'between:0,6'],
            'start_time' => ['sometimes', 'required', 'date_format:H:i:s'],
            'end_time' => ['sometimes', 'required', 'date_format:H:i:s'],
            'is_available' => ['sometimes', 'boolean'],
            'special_date' => ['nullable', 'date'],
        ];
    }
}
