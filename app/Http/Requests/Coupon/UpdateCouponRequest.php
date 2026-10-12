<?php

namespace App\Http\Requests\Coupon;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->role === 'admin';
    }

    public function rules(): array
    {
        $couponId = $this->route('coupon')?->id;

        return [
            'code' => ['sometimes', 'string', 'max:32', Rule::unique('coupons', 'code')->ignore($couponId)],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'discount_value' => ['sometimes', 'numeric', 'min:0'],
            'commission_rate' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'expiry_date' => ['nullable', 'date'],
            'is_used' => ['boolean'],
        ];
    }
}
