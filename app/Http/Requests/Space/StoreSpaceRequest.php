<?php

namespace App\Http\Requests\Space;

use Illuminate\Foundation\Http\FormRequest;

class StoreSpaceRequest extends FormRequest
{
    public function authorize(): bool
{
    $user = $this->user();
    return $user && $user->role === 'space_owner';
}


    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'location' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'space_size' => ['required', 'string', 'max:50'],
            'capacity_people' => ['required', 'integer', 'min:1'],
            'price_per_hour' => ['required', 'numeric', 'min:0'],
            'space_type' => ['required', 'string', 'max:50'],
            'device_type' => ['nullable', 'string', 'max:100'],
            'feature_ids' => ['nullable', 'array'],
            'feature_ids.*' => ['integer', 'exists:features,id'],
        ];
    }
}
