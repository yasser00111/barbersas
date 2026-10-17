<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Basic',
                'slug' => 'basic',
                'description' => 'Cocok untuk barbershop baru dengan satu cabang.',
                'price' => 99000,
                'currency' => 'IDR',
                'duration_days' => 30,
                'features' => ['Antrian Online', 'QR Code Barbershop', 'Hingga 3 Barber', 'Laporan Harian'],
                'max_barbers' => 3,
                'max_services' => 10,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Pro',
                'slug' => 'pro',
                'description' => 'Untuk barbershop yang sedang berkembang.',
                'price' => 199000,
                'currency' => 'IDR',
                'duration_days' => 30,
                'features' => ['Semua fitur Basic', 'Hingga 10 Barber', 'Laporan Mingguan & Bulanan', 'Pelanggan Tanpa Batas'],
                'max_barbers' => 10,
                'max_services' => 50,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Enterprise',
                'slug' => 'enterprise',
                'description' => 'Untuk jaringan barbershop multi-cabang.',
                'price' => 499000,
                'currency' => 'IDR',
                'duration_days' => 30,
                'features' => ['Semua fitur Pro', 'Barber Tanpa Batas', 'Prioritas Dukungan', 'Analitik Lanjutan'],
                'max_barbers' => null,
                'max_services' => null,
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::query()->updateOrCreate(
                ['slug' => $plan['slug']],
                $plan,
            );
        }
    }
}
