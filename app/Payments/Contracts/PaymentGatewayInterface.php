<?php

namespace App\Payments\Contracts;

use App\Models\Invoice;
use App\Payments\GatewayWebhook;
use App\Payments\PaymentSession;
use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    /**
     * Unique gateway identifier, e.g. "midtrans".
     */
    public function name(): string;

    /**
     * Create a provider-side payment session for the given invoice.
     */
    public function createCheckout(Invoice $invoice, array $options = []): PaymentSession;

    /**
     * Validate that an inbound webhook genuinely originated from the provider.
     */
    public function verifyWebhook(Request $request): bool;

    /**
     * Normalise an inbound webhook payload.
     */
    public function parseWebhook(Request $request): GatewayWebhook;

    /**
     * Query the provider for the authoritative status of a transaction.
     *
     * @return array<string, mixed>
     */
    public function retrieve(string $reference): array;
}
