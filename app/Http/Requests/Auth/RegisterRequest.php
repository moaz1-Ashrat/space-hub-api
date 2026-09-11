<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:100', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'phone' => ['required', 'string', 'max:15'],
            'gender' => ['required', 'in:male,female'],
            'role' => ['required', 'in:customer,owner'],

            // customer optional
            'favorite' => ['nullable', 'string'],

            // owner required
            'tax_registration_number' => ['required_if:role,owner', 'string', 'max:9', 'unique:space_owners,tax_registration_number'],
        ];
    }
    protected function prepareForValidation(): void
    {
    if ($this->filled('tax_registration_number')) {
        $this->merge([
            'tax_registration_number' => preg_replace('/\D/', '', $this->tax_registration_number),
        ]);
    }
     }
}
