<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;

class SubscriptionPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:120', 'alpha_dash'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price' => ['required', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'duration_days' => ['required', 'integer', 'min:1', 'max:3650'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'max:120'],
            'max_barbers' => ['nullable', 'integer', 'min:0'],
            'max_services' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
