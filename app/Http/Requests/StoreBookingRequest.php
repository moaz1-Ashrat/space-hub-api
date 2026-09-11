<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookingRequest extends FormRequest
{
    public function authorize()
    {
        // authenticated by sanctum middleware in routes
        return $this->user() !== null;
    }

    public function rules()
    {
        return [
            'space_id' => ['required', 'integer', 'exists:spaces,id'],
            'start_datetime' => ['required', 'date', 'after:now'],
            'end_datetime' => ['required', 'date', 'after:start_datetime'],
            'coupon_id' => ['nullable', 'integer', 'exists:coupons,id'],
        ];
    }
}
