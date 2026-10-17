<?php

namespace App\Payments;

use App\Models\Invoice;
use App\Payments\Contracts\PaymentGatewayInterface;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Offline bank-transfer gateway. No third-party checkout is created; the tenant
 * uploads a proof of transfer which a super admin verifies manually.
 */
class ManualGateway implements PaymentGatewayInterface
{
    public function name(): string
    {
        return 'manual';
    }

    public function createCheckout(Invoice $invoice, array $options = []): PaymentSession
    {
        return new PaymentSession(
            provider: $this->name(),
            reference: $invoice->number,
        );
    }

    public function verifyWebhook(Request $request): bool
    {
        return false;
    }

    public function parseWebhook(Request $request): GatewayWebhook
    {
        throw ValidationException::withMessages([
            'provider' => 'Gateway manual tidak menerima webhook.',
        ]);
    }

    public function retrieve(string $reference): array
    {
        return [];
    }
}
