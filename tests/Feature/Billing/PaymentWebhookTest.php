<?php

namespace Tests\Feature\Billing;

use App\Enums\InvoiceStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Invoice;
use App\Models\PaymentWebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
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

    protected function postWebhook(array $payload, ?string $signature = null)
    {
        $content = json_encode($payload);
        $signature ??= hash_hmac('sha256', $content, (string) config('app.key'));

        return $this->call('POST', '/webhooks/payments/fake', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_Fake_Signature' => $signature,
        ], $content);
    }

    protected function payload(Invoice $invoice, array $overrides = []): array
    {
        return array_merge([
            'event_id' => 'evt-1',
            'event' => 'payment',
            'status' => 'paid',
            'order_id' => $invoice->number,
            'transaction_id' => 'trx-1',
            'gross_amount' => (float) $invoice->amount,
            'currency' => 'IDR',
            'paid_at' => true,
        ], $overrides);
    }

    public function test_valid_paid_webhook_activates_subscription(): void
    {
        $invoice = $this->invoice();

        $this->postWebhook($this->payload($invoice))->assertOk();

        $this->assertSame(InvoiceStatus::Paid, $invoice->fresh()->status);
        $this->assertSame(SubscriptionStatus::Active, $invoice->subscription->fresh()->status);

        $event = PaymentWebhookEvent::firstOrFail();
        $this->assertTrue((bool) $event->signature_valid);
        $this->assertSame('processed', $event->status);
    }

    public function test_invalid_signature_is_rejected_and_ignored(): void
    {
        $invoice = $this->invoice();

        $this->postWebhook($this->payload($invoice), 'wrong-signature')->assertStatus(202);

        $this->assertSame(InvoiceStatus::Unpaid, $invoice->fresh()->status);

        $event = PaymentWebhookEvent::firstOrFail();
        $this->assertFalse((bool) $event->signature_valid);
        $this->assertSame('rejected', $event->status);
    }

    public function test_webhook_is_idempotent_for_repeated_event_id(): void
    {
        $invoice = $this->invoice();

        $this->postWebhook($this->payload($invoice))->assertOk();
        $endAfterFirst = $invoice->subscription->fresh()->ends_at->copy();

        $this->postWebhook($this->payload($invoice))->assertOk();

        $this->assertSame(1, PaymentWebhookEvent::count());
        $this->assertTrue($endAfterFirst->equalTo($invoice->subscription->fresh()->ends_at));
    }

    public function test_amount_mismatch_is_not_applied(): void
    {
        $invoice = $this->invoice();

        $this->postWebhook($this->payload($invoice, ['gross_amount' => 1000, 'event_id' => 'evt-amount']))->assertOk();

        $this->assertSame(InvoiceStatus::Unpaid, $invoice->fresh()->status);

        $event = PaymentWebhookEvent::firstOrFail();
        $this->assertSame('failed', $event->status);
    }

    public function test_unknown_invoice_is_ignored(): void
    {
        $this->postWebhook([
            'event_id' => 'evt-unknown',
            'status' => 'paid',
            'order_id' => 'INV-DOES-NOT-EXIST',
            'gross_amount' => 99000,
            'currency' => 'IDR',
        ])->assertOk();

        $event = PaymentWebhookEvent::firstOrFail();
        $this->assertSame('ignored', $event->status);
    }
}
