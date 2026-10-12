<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'user_id' => $this->user_id,
            'discount_value' => (float) $this->discount_value,
            'commission_rate' => $this->commission_rate !== null ? (float) $this->commission_rate : null,
            'is_used' => (bool) $this->is_used,
            'expiry_date' => $this->expiry_date?->toDateString(),
            'used_at' => $this->used_at?->toDateTimeString(),
            'booking_id' => $this->booking_id,
            'created_at' => $this->created_at,
        ];
    }
}
