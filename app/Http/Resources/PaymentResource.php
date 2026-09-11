<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'payment_methode' => $this->payment_methode,
            'payment_status' => $this->payment_status,
            'payment_date_time' => $this->payment_date_time,
            'booking' => $this->whenLoaded('booking', fn () => [
                'id' => $this->booking->id,
                'booking_status' => $this->booking->booking_status,
                'total_amount' => $this->booking->total_amount,
            ]),
        ];
    }
}
