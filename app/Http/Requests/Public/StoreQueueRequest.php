<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

class StoreQueueRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s]{6,30}$/'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'service_id' => ['required', 'integer'],
            'barber_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Nomor HP tidak valid.',
            'service_id.required' => 'Silakan pilih layanan.',
        ];
    }
}
