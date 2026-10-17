<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->randomElement(['Potong Rambut', 'Cukur Jenggot', 'Creambath', 'Cuci Rambut']),
            'description' => fake()->sentence(),
            'price' => fake()->numberBetween(20000, 100000),
            'duration_minutes' => fake()->randomElement([15, 30, 45, 60]),
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
