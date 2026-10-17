<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterTenantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'owner_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:30'],
            'business_name' => ['required', 'string', 'max:255'],
            'business_phone' => ['nullable', 'string', 'max:30'],
            'city' => ['nullable', 'string', 'max:120'],
            'plan_id' => ['required', 'integer', 'exists:subscription_plans,id'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Email ini sudah terdaftar.',
            'plan_id.exists' => 'Paket langganan yang dipilih tidak tersedia.',
        ];
    }
}
