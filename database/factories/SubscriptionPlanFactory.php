<?php

namespace Database\Factories;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SubscriptionPlan>
 */
class SubscriptionPlanFactory extends Factory
{
    protected $model = SubscriptionPlan::class;

    public function definition(): array
    {
        $name = 'Paket '.fake()->unique()->word();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(4)),
            'description' => fake()->sentence(),
            'price' => fake()->numberBetween(50000, 500000),
            'currency' => 'IDR',
            'duration_days' => 30,
            'features' => ['Antrian Online', 'QR Code'],
            'max_barbers' => 10,
            'max_services' => 20,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
