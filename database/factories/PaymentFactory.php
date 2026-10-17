<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'tenant_id' => Tenant::factory(),
            'method' => PaymentMethod::Manual,
            'provider' => 'manual',
            'provider_reference' => 'manual-'.Str::random(10),
            'amount' => 99000,
            'currency' => 'IDR',
            'status' => PaymentStatus::Pending,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::Paid,
            'paid_at' => now(),
            'verified_at' => now(),
        ]);
    }
}
