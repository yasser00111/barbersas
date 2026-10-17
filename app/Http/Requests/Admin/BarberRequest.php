<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class BarberRequest extends FormRequest
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
        $barberId = $this->route('barber')?->id;

        return [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'service_ids' => ['nullable', 'array'],
            'service_ids.*' => ['integer', 'exists:services,id'],

            'create_account' => ['boolean'],
            'account_email' => ['nullable', 'required_if:create_account,true', 'email', 'max:255', 'unique:users,email'.($this->input('account_user_id') ? ','.$this->input('account_user_id') : '')],
            'account_password' => ['nullable', 'required_if:create_account,true', Password::min(8)->letters()->numbers()],
        ];
    }
}
