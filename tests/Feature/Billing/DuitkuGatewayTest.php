<?php

namespace Tests\Feature\Billing;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentWebhookEvent;
use App\Models\User;
use App\Payments\DuitkuGateway;
use App\Support\PlatformConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class DuitkuGatewayTest extends TestCase
{
    use RefreshDatabase;

    protected string $merchantCode = 'D12345';
    protected string $apiKey = 'test-duitku-api-key';

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();

        config([
            'barbersaas.payment.gateways.duitku' => [
                'driver' => 'duitku',
                'merchant_code' => $this->merchantCode,
                'api_key' => $this->apiKey,
                'is_production' => false,
                'expiry_period' => 1440,
            ],
        ]);
    }

    protected function invoice(array $overrides = []): Invoice
    {
        $scenario = $this->pendingTenant();

        return Invoice::factory()->create(array_merge([
            'tenant_id' => $scenario['tenant']->id,
            'subscription_id' => $scenario['subscription']->id,
            'amount' => 99000,
            'currency' => 'IDR',
            'status' => InvoiceStatus::Unpaid,
        ], $overrides));
    }

    public function test_create_checkout_sends_correct_payload_and_returns_session(): void
    {
        $invoice = $this->invoice();

        Http::fake([
            'https://sandbox.duitku.com/web/api/merchant/v2/inquiry.php' => Http::response([
                'merchantCode' => $this->merchantCode,
                'reference' => 'DUITKU-REF-12345',
                'paymentUrl' => 'https://sandbox.duitku.com/web/checkout.php?reference=DUITKU-REF-12345',
                'statusCode' => '00',
                'statusMessage' => 'SUCCESS',
            ], 200),
        ]);

        $gateway = new DuitkuGateway(config('barbersaas.payment.gateways.duitku'));
        $session = $gateway->createCheckout($invoice);

        $this->assertSame('duitku', $session->provider);
        $this->assertSame('https://sandbox.duitku.com/web/checkout.php?reference=DUITKU-REF-12345', $session->redirectUrl);
        $this->assertSame('DUITKU-REF-12345', $session->token);

        Http::assertSent(function ($request) use ($invoice) {
            $data = $request->data();
            $expectedSig = md5($this->merchantCode . $invoice->number . 99000 . $this->apiKey);

            return $data['merchantCode'] === $this->merchantCode
                && $data['merchantOrderId'] === $invoice->number
                && $data['paymentAmount'] === 99000
                && $data['signature'] === $expectedSig;
        });
    }

    public function test_valid_duitku_webhook_activates_subscription(): void
    {
        $invoice = $this->invoice();
        $amount = '99000';
        $orderId = $invoice->number;
        $signature = md5($this->merchantCode . $amount . $orderId . $this->apiKey);

        $payload = [
            'merchantCode' => $this->merchantCode,
            'amount' => $amount,
            'merchantOrderId' => $orderId,
            'signature' => $signature,
            'resultCode' => '00',
            'reference' => 'DUITKU-REF-001',
            'paymentCode' => 'VA',
        ];

        $response = $this->postJson('/webhooks/payments/duitku', $payload);
        $response->assertOk();

        $this->assertSame(InvoiceStatus::Paid, $invoice->fresh()->status);
        $this->assertSame(SubscriptionStatus::Active, $invoice->subscription->fresh()->status);

        $event = PaymentWebhookEvent::firstOrFail();
        $this->assertSame('duitku', $event->provider);
        $this->assertTrue((bool) $event->signature_valid);
        $this->assertSame('processed', $event->status);

        $payment = Payment::withoutTenantScope()->where('invoice_id', $invoice->id)->firstOrFail();
        $this->assertSame(PaymentMethod::Duitku, $payment->method);
        $this->assertSame(PaymentStatus::Paid, $payment->status);
    }

    public function test_invalid_signature_duitku_webhook_is_rejected(): void
    {
        $invoice = $this->invoice();

        $payload = [
            'merchantCode' => $this->merchantCode,
            'amount' => '99000',
            'merchantOrderId' => $invoice->number,
            'signature' => 'invalid-signature-hash',
            'resultCode' => '00',
            'reference' => 'DUITKU-REF-002',
        ];

        $response = $this->postJson('/webhooks/payments/duitku', $payload);
        $response->assertStatus(202);

        $this->assertSame(InvoiceStatus::Unpaid, $invoice->fresh()->status);

        $event = PaymentWebhookEvent::firstOrFail();
        $this->assertFalse((bool) $event->signature_valid);
        $this->assertSame('rejected', $event->status);
    }

    public function test_super_admin_can_save_duitku_settings(): void
    {
        $superAdmin = $this->superAdmin();

        $response = $this->actingAs($superAdmin)
            ->put(route('super-admin.settings.update'), [
                'payment_gateway' => 'duitku',
                'duitku_merchant_code' => 'D99999',
                'duitku_api_key' => 'secret-key-1234',
                'duitku_is_production' => false,
            ]);

        $response->assertRedirect();

        $this->assertSame('duitku', PlatformConfig::get('payment_gateway'));
        $this->assertSame('D99999', PlatformConfig::get('duitku_merchant_code'));
        $this->assertSame('secret-key-1234', PlatformConfig::get('duitku_api_key'));
        $this->assertSame('duitku', config('barbersaas.payment.default'));
        $this->assertSame('D99999', config('barbersaas.payment.gateways.duitku.merchant_code'));
    }
}
