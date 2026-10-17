<?php

namespace App\Payments;

use Carbon\CarbonInterface;

class PaymentSession
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $provider,
        public string $reference,
        public ?string $token = null,
        public ?string $redirectUrl = null,
        public ?CarbonInterface $expiresAt = null,
        public array $raw = [],
    ) {}
}
