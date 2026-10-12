<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    public function rules(): array
    {
        return [
            'space_id' => ['required', 'integer', 'exists:spaces,id'],
            'start_datetime' => ['required', 'date', 'after:now'],
            'end_datetime' => ['required', 'date', 'after:start_datetime'],
            'coupon_code' => ['nullable', 'string', 'max:32'],
            'coupon_id' => ['nullable', 'integer', 'exists:coupons,id'],
        ];
    }
}
