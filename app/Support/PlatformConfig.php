<?php

namespace App\Support;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;

/**
 * Platform-level (super admin) configuration stored in the database.
 * Sensitive values (SMTP password, gateway secrets) are encrypted at rest.
 */
class PlatformConfig
{
    public const CACHE_KEY = 'platform_settings';

    /**
     * @return array<string, string|null>
     */
    public static function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return PlatformSetting::query()
                ->get()
                ->mapWithKeys(fn (PlatformSetting $setting) => [
                    $setting->key => $setting->resolvedValue(),
                ])
                ->all();
        });
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $settings = self::all();

        return array_key_exists($key, $settings) && $settings[$key] !== null
            ? $settings[$key]
            : $default;
    }

    public static function set(string $key, mixed $value, bool $encrypted = false): void
    {
        $stored = $encrypted && $value !== null && $value !== ''
            ? Crypt::encryptString((string) $value)
            : $value;

        PlatformSetting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $stored, 'is_encrypted' => $encrypted && $value !== null && $value !== ''],
        );

        self::forget();
    }

    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
