<?php

namespace App\Payments;

use App\Models\Invoice;
use App\Payments\Contracts\PaymentGatewayInterface;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

/**
 * Midtrans Snap adapter.
 *
 * Implemented with the HTTP client (no third-party SDK) so the integration is
 * transparent and does not pin the application to an incompatible package.
 * All credentials are read from configuration/environment.
 */
class MidtransGateway implements PaymentGatewayInterface
{
    public function __construct(protected array $config) {}

    public function name(): string
    {
        return 'midtrans';
    }

    protected function serverKey(): string
    {
        return (string) ($this->config['server_key'] ?? '');
    }

    protected function isProduction(): bool
    {
        return (bool) ($this->config['is_production'] ?? false);
    }

    protected function baseUrl(): string
    {
        if (! empty($this->config['snap_base_url'])) {
            return rtrim((string) $this->config['snap_base_url'], '/');
        }

        return $this->isProduction()
            ? 'https://app.midtrans.com'
            : 'https://app.sandbox.midtrans.com';
    }

    protected function authHeader(): array
    {
        return [
            'Authorization' => 'Basic '.base64_encode($this->serverKey().':'),
            'Accept' => 'application/json',
        ];
    }

    public function createCheckout(Invoice $invoice, array $options = []): PaymentSession
    {
        $tenant = $invoice->tenant;
        $orderId = $options['order_id'] ?? $invoice->number;

        // If this invoice already had payment attempts, append an attempt suffix so Midtrans treats it as a fresh transaction
        $attempts = \App\Models\Payment::withoutTenantScope()
            ->where('invoice_id', $invoice->getKey())
            ->where('provider', $this->name())
            ->count();

        if ($attempts > 0 && ! str_contains($orderId, '-attempt-')) {
            $orderId .= '-attempt-' . ($attempts + 1);
        }

        $grossAmount = (int) round((float) $invoice->amount);

        $payload = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => $grossAmount,
            ],
            'item_details' => [[
                'id' => (string) ($invoice->subscription_id ?: $invoice->getKey()),
                'price' => $grossAmount,
                'quantity' => 1,
                'name' => mb_substr((string) ($invoice->plan_name ?: 'Langganan'), 0, 50),
            ]],
            'customer_details' => [
                'first_name' => mb_substr((string) ($tenant?->name ?? 'Tenant'), 0, 50),
                'email' => $tenant?->email,
                'phone' => $tenant?->phone,
            ],
            'callbacks' => [
                'finish' => route('admin.subscription.index'),
            ],
        ];

        $response = Http::withHeaders($this->authHeader())
            ->baseUrl($this->baseUrl())
            ->post('/snap/v1/transactions', $payload);

        $this->ensureSuccess($response);

        $body = $response->json();

        if (empty($body['token']) || empty($body['redirect_url'])) {
            throw ValidationException::withMessages([
                'gateway' => 'Midtrans tidak mengembalikan token pembayaran yang valid.',
            ]);
        }

        return new PaymentSession(
            provider: $this->name(),
            reference: $orderId,
            token: $body['token'],
            redirectUrl: $body['redirect_url'],
            expiresAt: now()->addDay(),
            raw: $body,
        );
    }

    public function verifyWebhook(Request $request): bool
    {
        $payload = $request->all();

        foreach (['order_id', 'status_code', 'gross_amount', 'signature_key'] as $field) {
            if (empty($payload[$field])) {
                return false;
            }
        }

        $expected = hash(
            'sha512',
            $payload['order_id'].$payload['status_code'].$payload['gross_amount'].$this->serverKey(),
        );

        return hash_equals($expected, (string) $payload['signature_key']);
    }

    public function parseWebhook(Request $request): GatewayWebhook
    {
        $payload = $request->all();
        $status = $this->normaliseStatus(
            (string) ($payload['transaction_status'] ?? ''),
            (string) ($payload['fraud_status'] ?? ''),
        );

        return new GatewayWebhook(
            provider: $this->name(),
            eventId: (string) ($payload['transaction_id'] ?? $payload['order_id'] ?? ''),
            type: (string) ($payload['transaction_status'] ?? 'unknown'),
            status: $status,
            orderId: (string) ($payload['order_id'] ?? ''),
            externalId: isset($payload['transaction_id']) ? (string) $payload['transaction_id'] : null,
            amount: (float) ($payload['gross_amount'] ?? 0),
            currency: (string) ($payload['currency'] ?? 'IDR'),
            paidAt: $this->resolvePaidAt($payload),
            raw: $payload,
        );
    }

    public function retrieve(string $reference): array
    {
        $response = Http::withHeaders($this->authHeader())
            ->baseUrl($this->baseUrl())
            ->get('/v2/'.$reference.'/status');

        if ($response->failed()) {
            return [];
        }

        return $response->json() ?? [];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function resolvePaidAt(array $payload): ?Carbon
    {
        $value = $payload['settlement_time'] ?? $payload['transaction_time'] ?? null;

        if (! $value) {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function normaliseStatus(string $transactionStatus, string $fraudStatus): string
    {
        return match ($transactionStatus) {
            'capture' => $fraudStatus === 'challenge' ? 'pending' : 'paid',
            'settlement' => 'paid',
            'pending' => 'pending',
            'deny', 'cancel', 'failure' => 'failed',
            'expire' => 'expired',
            'refund', 'partial_refund' => 'refunded',
            'chargeback', 'partial_chargeback' => 'chargeback',
            default => 'pending',
        };
    }

    protected function ensureSuccess(Response $response): void
    {
        if ($response->failed()) {
            report(new \RuntimeException('Midtrans error: '.$response->body()));

            $errorMessage = $response->json('error_messages.0') ?? 'Gagal membuat sesi pembayaran dengan Midtrans.';

            throw ValidationException::withMessages([
                'gateway' => $errorMessage,
            ]);
        }
    }
}
