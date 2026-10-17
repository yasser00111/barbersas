<?php

namespace Database\Factories;

use App\Enums\QueueSource;
use App\Enums\QueueStatus;
use App\Models\Barber;
use App\Models\Customer;
use App\Models\Queue;
use App\Models\Service;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Queue>
 */
class QueueFactory extends Factory
{
    protected $model = Queue::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'customer_id' => Customer::factory(),
            'barber_id' => null,
            'service_id' => Service::factory(),
            'queue_date' => now()->toDateString(),
            'queue_number' => fake()->unique()->numberBetween(1, 9999),
            'status' => QueueStatus::Waiting,
            'source' => QueueSource::Online,
        ];
    }
}
