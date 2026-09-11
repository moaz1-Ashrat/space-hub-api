<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpaceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'location' => $this->location,
            'description' => $this->description,
            'space_size' => $this->space_size,
            'capacity_people' => $this->capacity_people,
            'price_per_hour' => $this->price_per_hour,
            'space_type' => $this->space_type,
            'device_type' => $this->device_type,
            'approval_status' => $this->approval_status,
            'owner_name' => optional($this->owner)->first_name . ' ' . optional($this->owner)->last_name,
            'average_rating' => $this->when(isset($this->avg_rating), round((float)$this->avg_rating, 2)),
            'features' => FeatureResource::collection($this->whenLoaded('features')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
