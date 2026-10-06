<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpaceImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'space_id' => $this->space_id,
            'url' => $this->url,
            'order' => $this->order,
            'is_primary' => $this->is_primary,
            'created_at' => $this->created_at,
        ];
    }
}