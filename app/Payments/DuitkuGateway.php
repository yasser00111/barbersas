<?php

namespace App\Payments;

use App\Models\Invoice;
use App\Models\Payment;
use App\Payments\Contracts\PaymentGatewayInterface;
use Carbon\Carbon;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

/**
 * Duitku adapter.
 *
 * Implemented using Laravel HTTP client without external SDKs.
 * Uses Duitku Inquiry API for payment session and handles callback signature verification.
 */
class DuitkuGateway implements PaymentGatewayInterface
{
    public function __construct(protected array $config) {}

    public function name(): string
    {
        return 'duitku';
    }

    protected function merchantCode(): string
    {
        return (string) ($this->config['merchant_code'] ?? '');
    }

    protected function apiKey(): string
    {
        return (string) ($this->config['api_key'] ?? '');
    }

    protected function isProduction(): bool
    {
        return (bool) ($this->config['is_production'] ?? false);
    }

    protected function inquiryUrl(): string
    {
        return $this->isProduction()
            ? 'https://passport.duitku.com/web/api/merchant/v2/inquiry.php'
            : 'https://sandbox.duitku.com/web/api/merchant/v2/inquiry.php';
    }

    protected function statusUrl(): string
    {
        return $this->isProduction()
            ? 'https://passport.duitku.com/web/api/merchant/transactionStatus'
            : 'https://sandbox.duitku.com/web/api/merchant/transactionStatus';
    }

    public function createCheckout(Invoice $invoice, array $options = []): PaymentSession
    {
        $tenant = $invoice->tenant;
        $orderId = $options['order_id'] ?? $invoice->number;

        // Ensure fresh order ID if retry
        $attempts = Payment::withoutTenantScope()
            ->where('invoice_id', $invoice->getKey())
            ->where('provider', $this->name())
            ->count();

        if ($attempts > 0 && ! str_contains($orderId, '-attempt-')) {
            $orderId .= '-attempt-' . ($attempts + 1);
        }

        $paymentAmount = (int) round((float) $invoice->amount);
        $merchantCode = $this->merchantCode();
        $apiKey = $this->apiKey();

        if (empty($merchantCode) || empty($apiKey)) {
            throw ValidationException::withMessages([
                'gateway' => 'Kredensial Duitku (Merchant Code / API Key) belum diatur oleh Super Admin.',
            ]);
        }

        // Signature: md5(merchantCode + merchantOrderId + paymentAmount + apiKey)
        $signature = md5($merchantCode . $orderId . $paymentAmount . $apiKey);

        $customerEmail = $tenant?->email ?: 'customer@barbersas.test';
        $customerPhone = $tenant?->phone ?: '08123456789';
        $customerName = mb_substr((string) ($tenant?->name ?? 'Pelanggan'), 0, 50);
        $productDetails = mb_substr((string) ($invoice->plan_name ?: 'Langganan BarberSaaS'), 0, 50);

        $callbackUrl = route('webhooks.payments', ['gateway' => 'duitku']);
        $returnUrl = route('admin.subscription.index');

        $payload = [
            'merchantCode' => $merchantCode,
            'paymentAmount' => $paymentAmount,
            'paymentMethod' => $options['payment_method'] ?? '', // empty = customer chooses on Duitku page
            'merchantOrderId' => $orderId,
            'productDetails' => $productDetails,
            'email' => $customerEmail,
            'phoneNumber' => $customerPhone,
            'customerVaName' => $customerName,
            'callbackUrl' => $callbackUrl,
            'returnUrl' => $returnUrl,
            'signature' => $signature,
            'expiryPeriod' => (int) ($this->config['expiry_period'] ?? 1440),
        ];

        $response = Http::asJson()
            ->acceptJson()
            ->timeout(15)
            ->post($this->inquiryUrl(), $payload);

        $this->ensureSuccess($response);

        $body = $response->json();

        if (empty($body['paymentUrl'])) {
            throw ValidationException::withMessages([
                'gateway' => 'Duitku tidak mengembalikan URL pembayaran: ' . ($body['statusMessage'] ?? 'Unknown response'),
            ]);
        }

        return new PaymentSession(
            provider: $this->name(),
            reference: $orderId,
            token: (string) ($body['reference'] ?? $orderId),
            redirectUrl: (string) $body['paymentUrl'],
            expiresAt: now()->addDay(),
            raw: $body,
        );
    }

    public function verifyWebhook(Request $request): bool
    {
        $merchantCode = (string) $request->input('merchantCode', '');
        $amount = (string) $request->input('amount', '');
        $merchantOrderId = (string) $request->input('merchantOrderId', '');
        $signature = (string) $request->input('signature', '');

        if (empty($merchantCode) || empty($amount) || empty($merchantOrderId) || empty($signature)) {
            return false;
        }

        // Duitku callback signature: md5(merchantCode + amount + merchantOrderId + apiKey)
        $expected = md5($merchantCode . $amount . $merchantOrderId . $this->apiKey());

        return hash_equals($expected, $signature);
    }

    public function parseWebhook(Request $request): GatewayWebhook
    {
        $payload = $request->all();
        $resultCode = (string) ($payload['resultCode'] ?? '');
        $orderId = (string) ($payload['merchantOrderId'] ?? '');
        $reference = (string) ($payload['reference'] ?? '');

        // resultCode 00 = Success, 01 = Failed
        $status = match ($resultCode) {
            '00' => 'paid',
            '01' => 'failed',
            default => 'pending',
        };

        return new GatewayWebhook(
            provider: $this->name(),
            eventId: $reference ?: ($orderId . '-' . $resultCode),
            type: 'duitku.callback',
            status: $status,
            orderId: $orderId,
            externalId: $reference ?: null,
            amount: (float) ($payload['amount'] ?? 0),
            currency: 'IDR',
            paidAt: $status === 'paid' ? now() : null,
            raw: $payload,
        );
    }

    public function retrieve(string $reference): array
    {
        $merchantCode = $this->merchantCode();
        $apiKey = $this->apiKey();
        $signature = md5($merchantCode . $reference . $apiKey);

        $response = Http::asJson()
            ->acceptJson()
            ->timeout(10)
            ->post($this->statusUrl(), [
                'merchantCode' => $merchantCode,
                'merchantOrderId' => $reference,
                'signature' => $signature,
            ]);

        if ($response->failed()) {
            return [];
        }

        return $response->json() ?? [];
    }

    protected function ensureSuccess(Response $response): void
    {
        if ($response->failed()) {
            report(new \RuntimeException('Duitku HTTP error: ' . $response->body()));

            $msg = $response->json('statusMessage') ?? 'Gagal membuat sesi pembayaran dengan Duitku.';

            throw ValidationException::withMessages([
                'gateway' => $msg,
            ]);
        }

        $body = $response->json();
        if (isset($body['statusCode']) && $body['statusCode'] !== '00') {
            $msg = $body['statusMessage'] ?? 'Duitku error: kode ' . $body['statusCode'];

            throw ValidationException::withMessages([
                'gateway' => $msg,
            ]);
        }
    }
}
