<?php

namespace App\Http\Requests\Coupon;

use Illuminate\Foundation\Http\FormRequest;

class StoreCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:32', 'unique:coupons,code'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'discount_value' => ['required', 'numeric', 'min:0'],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'expiry_date' => ['nullable', 'date', 'after:today'],
            'is_used' => ['boolean'],
            'coupon_code' => ['nullable', 'string', 'max:32'],
'coupon_id' => ['nullable', 'integer', 'exists:coupons,id'],
        ];
    }
}
