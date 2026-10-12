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
            'capacity_people' => ['required', 'integer', 'min:1', 'max:10000'],
            'price_per_hour' => ['required', 'numeric', 'min:1', 'max:100000'],
            'space_type' => ['required', 'string', 'max:50'],
            'device_type' => ['nullable', 'string', 'max:100'],
            'feature_ids' => ['nullable', 'array'],
            'feature_ids.*' => ['integer', 'exists:features,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'price_per_hour.min' => 'The price per hour must be at least 1 EGP.',
            'price_per_hour.max' => 'The price per hour cannot exceed 100,000 EGP.',
        ];
    }
}