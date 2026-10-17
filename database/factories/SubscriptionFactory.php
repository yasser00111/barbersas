<?php

namespace Database\Factories;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'subscription_plan_id' => SubscriptionPlan::factory(),
            'status' => SubscriptionStatus::Pending,
            'price' => 99000,
            'currency' => 'IDR',
            'duration_days' => 30,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'status' => SubscriptionStatus::Active,
            'starts_at' => now()->subDays(2),
            'ends_at' => now()->addDays(28),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'status' => SubscriptionStatus::Expired,
            'starts_at' => now()->subDays(40),
            'ends_at' => now()->subDay(),
        ]);
    }
}
