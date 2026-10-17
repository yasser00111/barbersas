<?php

namespace Database\Seeders;

use App\Support\PlatformConfig;
use Illuminate\Database\Seeder;

class PlatformSettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'payment_gateway' => config('barbersaas.payment.default', 'manual'),
            'grace_period_days' => (int) config('barbersaas.subscription.grace_days', 0),
            'mail_from_name' => config('app.name', 'BarberSaaS'),
        ];

        foreach ($defaults as $key => $value) {
            if (PlatformConfig::get($key) === null) {
                PlatformConfig::set($key, $value);
            }
        }
    }
}
