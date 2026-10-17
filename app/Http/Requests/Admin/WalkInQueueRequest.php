<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class WalkInQueueRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isTenantAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'barber_id' => ['nullable', 'integer', 'exists:barbers,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
