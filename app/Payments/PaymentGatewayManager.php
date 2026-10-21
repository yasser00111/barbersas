<?php

namespace App\Payments;

use App\Payments\Contracts\PaymentGatewayInterface;
use InvalidArgumentException;

class PaymentGatewayManager
{
    /**
     * @param  array<string, array<string, mixed>>  $gateways
     */
    public function __construct(protected array $gateways = [], protected ?string $default = null) {}

    public function default(): PaymentGatewayInterface
    {
        return $this->driver($this->default ?? 'manual');
    }

    public function driver(string $name): PaymentGatewayInterface
    {
        $config = $this->gateways[$name] ?? null;

        if ($config === null) {
            throw new InvalidArgumentException("Payment gateway [{$name}] is not configured.");
        }

        return match ($config['driver'] ?? $name) {
            'midtrans' => new MidtransGateway($config),
            'duitku' => new DuitkuGateway($config),
            'fake' => new FakeGateway(),
            'manual' => new ManualGateway(),
            default => throw new InvalidArgumentException("Unsupported payment gateway driver [{$name}]."),
        };
    }
}
