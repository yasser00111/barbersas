<?php

namespace App\Payments;

use App\Models\Invoice;
use App\Payments\Contracts\PaymentGatewayInterface;
use Illuminate\Http\Request;

/**
 * Deterministic gateway used in automated tests and local development. It does
 * not call any external service.
 */
class FakeGateway implements PaymentGatewayInterface
{
    public function name(): string
    {
        return 'fake';
    }

    public function createCheckout(Invoice $invoice, array $options = []): PaymentSession
    {
        return new PaymentSession(
            provider: $this->name(),
            reference: $invoice->number,
            token: 'fake-token-'.$invoice->uuid,
            redirectUrl: url('/payments/fake/'.$invoice->uuid),
            expiresAt: now()->addDay(),
        );
    }

    public function verifyWebhook(Request $request): bool
    {
        return $request->header('X-Fake-Signature') === $this->signature($request);
    }

    public function parseWebhook(Request $request): GatewayWebhook
    {
        return new GatewayWebhook(
            provider: $this->name(),
            eventId: (string) $request->input('event_id'),
            type: (string) $request->input('event', 'payment'),
            status: (string) $request->input('status', 'pending'),
            orderId: (string) $request->input('order_id'),
            externalId: (string) $request->input('transaction_id'),
            amount: (float) $request->input('gross_amount', 0),
            currency: (string) $request->input('currency', 'IDR'),
            paidAt: $request->input('paid_at') ? now() : null,
            raw: $request->all(),
        );
    }

    public function retrieve(string $reference): array
    {
        return ['order_id' => $reference, 'transaction_status' => 'settlement'];
    }

    public function signature(Request $request): string
    {
        return hash_hmac('sha256', $request->getContent(), (string) config('app.key'));
    }
}
