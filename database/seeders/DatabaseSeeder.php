<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SubscriptionPlanSeeder::class,
            PlatformSettingSeeder::class,
            SuperAdminSeeder::class,
        ]);

        if (app()->environment('local', 'development')) {
            $this->call(DemoSeeder::class);
        }
    }
}
