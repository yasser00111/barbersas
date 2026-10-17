<?php

namespace Database\Factories;

use App\Enums\TenantStatus;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.Str::lower(Str::random(5)),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('08##########'),
            'address' => fake()->address(),
            'city' => fake()->city(),
            'timezone' => 'Asia/Jakarta',
            'status' => TenantStatus::Active,
            'activated_at' => now(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => TenantStatus::Pending,
            'activated_at' => null,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn () => [
            'status' => TenantStatus::Suspended,
            'suspended_at' => now(),
            'suspended_reason' => 'Pelanggaran kebijakan.',
        ]);
    }
}
