<?php

namespace App\Support;

use App\Models\Tenant;
use App\Models\TenantSetting;

class TenantSettings
{
    public function __construct(protected Tenant $tenant) {}

    public static function for(Tenant $tenant): self
    {
        return new self($tenant);
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaults(): array
    {
        return [
            'tagline' => null,
            'booking_enabled' => true,
            'require_barber_selection' => false,
            'allow_online_cancel' => true,
            'daily_queue_limit' => 0, // 0 = unlimited
            'assignment_policy' => 'least_busy',
            'payment_bank_name' => null,
            'payment_bank_account' => null,
            'payment_account_name' => null,
            'payment_instructions' => null,
        ];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $setting = $this->tenant->settings()->where('key', $key)->first();

        if (! $setting || $setting->value === null) {
            return $default ?? (self::defaults()[$key] ?? null);
        }

        return $this->cast($setting->value);
    }

    public function set(string $key, mixed $value): void
    {
        $encoded = is_array($value) || is_bool($value)
            ? json_encode($value)
            : (string) $value;

        TenantSetting::query()->updateOrCreate(
            ['tenant_id' => $this->tenant->getKey(), 'key' => $key],
            ['value' => $encoded],
        );

        $this->tenant->unsetRelation('settings');
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $values = self::defaults();

        foreach ($this->tenant->settings as $setting) {
            $values[$setting->key] = $this->cast($setting->value);
        }

        return $values;
    }

    protected function cast(?string $value): mixed
    {
        if ($value === null) {
            return null;
        }

        $decoded = json_decode($value, true);

        if (json_last_error() === JSON_ERROR_NONE && (is_array($decoded) || is_bool($decoded))) {
            return $decoded;
        }

        return $value;
    }
}
