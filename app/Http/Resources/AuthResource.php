<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => trim($this->first_name . ' ' . $this->last_name),
            'email' => $this->email,
            'role' => $this->role,
            'phone' => $this->phone,
        ];
    }
}
