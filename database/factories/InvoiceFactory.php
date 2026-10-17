<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'subscription_id' => Subscription::factory(),
            'number' => 'INV-'.now()->format('Ym').'-'.strtoupper(Str::random(6)),
            'type' => InvoiceType::New,
            'status' => InvoiceStatus::Unpaid,
            'amount' => 99000,
            'currency' => 'IDR',
            'plan_name' => 'Paket Basic',
            'duration_days' => 30,
            'issued_at' => now(),
            'due_at' => now()->addDays(30),
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => InvoiceStatus::Paid,
            'paid_at' => now(),
        ]);
    }
}
