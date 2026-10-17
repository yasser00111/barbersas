<?php

namespace App\Payments;

use Carbon\CarbonInterface;

/**
 * Normalised representation of an inbound gateway notification so the billing
 * service never has to know provider-specific field names.
 */
class GatewayWebhook
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(
        public string $provider,
        public string $eventId,
        public string $type,
        public string $status,
        public string $orderId,
        public ?string $externalId = null,
        public float $amount = 0.0,
        public string $currency = 'IDR',
        public ?CarbonInterface $paidAt = null,
        public array $raw = [],
    ) {}
}
