<?php

namespace Database\Factories;

use App\Models\Barber;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Barber>
 */
class BarberFactory extends Factory
{
    protected $model = Barber::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->name(),
            'phone' => fake()->numerify('08##########'),
            'bio' => fake()->sentence(),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
