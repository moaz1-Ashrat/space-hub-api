<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'rating' => (int) $this->rating,
            'comment' => $this->comment,
            'review_date' => $this->review_date,
            'customer_first_name' => $this->whenLoaded('customer', function () {
                return optional($this->customer->user)->first_name;
            }),
        ];
    }
}
