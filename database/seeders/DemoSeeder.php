<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Enums\SubscriptionStatus;
use App\Models\Barber;
use App\Models\Service;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantMember;
use App\Models\User;
use App\Support\TenantSettings;
use Illuminate\Database\Seeder;

/**
 * Local development data. Never run in production.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $plan = SubscriptionPlan::query()->where('is_active', true)->orderBy('sort_order')->first();

        if (! $plan) {
            return;
        }

        $tenant = Tenant::query()->firstOrCreate(
            ['slug' => 'barber-keren'],
            [
                'name' => 'Barber Keren',
                'email' => 'owner@barberkeren.test',
                'phone' => '081234567890',
                'address' => 'Jl. Merdeka No. 1',
                'city' => 'Jakarta',
                'status' => 'active',
                'activated_at' => now(),
                'timezone' => 'Asia/Jakarta',
            ],
        );

        $owner = User::query()->updateOrCreate(
            ['email' => 'owner@barberkeren.test'],
            [
                'name' => 'Budi Owner',
                'phone' => '081234567890',
                'password' => 'password123',
                'role' => Role::TenantAdmin,
                'status' => 'active',
                'tenant_id' => $tenant->getKey(),
                'email_verified_at' => now(),
            ],
        );

        $tenant->forceFill(['owner_user_id' => $owner->getKey()])->save();

        TenantMember::query()->updateOrCreate(
            ['tenant_id' => $tenant->getKey(), 'user_id' => $owner->getKey()],
            ['role' => 'owner', 'status' => 'active', 'joined_at' => now()],
        );

        foreach (range(0, 6) as $day) {
            $isOpen = $day >= 1 && $day <= 6;
            $tenant->businessHours()->updateOrCreate(
                ['day_of_week' => $day],
                [
                    'is_open' => $isOpen,
                    'open_time' => $isOpen ? '09:00' : null,
                    'close_time' => $isOpen ? '21:00' : null,
                    'break_start' => $isOpen ? '12:00' : null,
                    'break_end' => $isOpen ? '13:00' : null,
                ],
            );
        }

        $settings = TenantSettings::for($tenant);
        foreach (TenantSettings::defaults() as $key => $value) {
            $settings->set($key, $value);
        }
        $settings->set('tagline', 'Potongan rapi, gaya percaya diri.');
        $settings->set('payment_bank_name', 'BCA');
        $settings->set('payment_bank_account', '1234567890');
        $settings->set('payment_account_name', 'PT Barber Keren');

        $subscription = Subscription::query()->firstOrCreate(
            ['tenant_id' => $tenant->getKey(), 'status' => SubscriptionStatus::Active->value],
            [
                'subscription_plan_id' => $plan->getKey(),
                'price' => $plan->price,
                'currency' => $plan->currency,
                'duration_days' => $plan->duration_days,
                'starts_at' => now(),
                'ends_at' => now()->addDays(30),
            ],
        );
        $subscription->forceFill(['ends_at' => now()->addDays(30)])->save();

        $services = [
            ['name' => 'Potong Rambut', 'price' => 35000, 'duration_minutes' => 30],
            ['name' => 'Cukur Jenggot', 'price' => 25000, 'duration_minutes' => 20],
            ['name' => 'Potong + Cukur', 'price' => 55000, 'duration_minutes' => 45],
        ];

        foreach ($services as $i => $service) {
            Service::query()->updateOrCreate(
                ['tenant_id' => $tenant->getKey(), 'name' => $service['name']],
                array_merge($service, ['is_active' => true, 'sort_order' => $i]),
            );
        }

        $barbers = [
            ['name' => 'Andi', 'phone' => '081200000001'],
            ['name' => 'Rudi', 'phone' => '081200000002'],
        ];

        foreach ($barbers as $i => $barber) {
            Barber::query()->updateOrCreate(
                ['tenant_id' => $tenant->getKey(), 'name' => $barber['name']],
                array_merge($barber, ['is_active' => true, 'sort_order' => $i]),
            );
        }
    }
}
