<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Events\InvoiceIssued;
use App\Models\EmailLog;
use App\Models\Invoice;
use App\Models\Payment;
use App\Notifications\InvoiceIssuedNotification;
use App\Notifications\PaymentFailedNotification;
use App\Notifications\PaymentVerifiedNotification;
use App\Notifications\SubscriptionActivatedNotification;
use App\Notifications\TenantSuspendedNotification;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_invoice_issued_notifies_tenant_owner(): void
    {
        $scenario = $this->pendingTenant();

        app(InvoiceService::class)->createForSubscription($scenario['tenant'], $scenario['subscription']);

        Notification::assertSentTo($scenario['owner'], InvoiceIssuedNotification::class);
    }

    public function test_invoice_notification_is_deduplicated(): void
    {
        $scenario = $this->pendingTenant();

        $invoice = Invoice::factory()->create([
            'tenant_id' => $scenario['tenant']->id,
            'subscription_id' => $scenario['subscription']->id,
        ]);

        Notification::fake();
        InvoiceIssued::dispatch($invoice);
        InvoiceIssued::dispatch($invoice);

        Notification::assertSentToTimes($scenario['owner'], InvoiceIssuedNotification::class, 1);
        $this->assertSame(1, EmailLog::where('dedupe_key', 'invoice-issued:'.$invoice->uuid)->count());
    }

    public function test_payment_verification_notifies_owner_and_activates_subscription(): void
    {
        $scenario = $this->pendingTenant();
        $invoice = Invoice::factory()->create([
            'tenant_id' => $scenario['tenant']->id,
            'subscription_id' => $scenario['subscription']->id,
            'status' => InvoiceStatus::Pending,
        ]);
        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'tenant_id' => $scenario['tenant']->id,
            'status' => PaymentStatus::Pending,
        ]);

        app(PaymentService::class)->approve($payment, $this->superAdmin());

        Notification::assertSentTo($scenario['owner'], PaymentVerifiedNotification::class);
        Notification::assertSentTo($scenario['owner'], SubscriptionActivatedNotification::class);
    }

    public function test_payment_rejection_notifies_owner(): void
    {
        $scenario = $this->pendingTenant();
        $invoice = Invoice::factory()->create([
            'tenant_id' => $scenario['tenant']->id,
            'subscription_id' => $scenario['subscription']->id,
            'status' => InvoiceStatus::Pending,
        ]);
        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'tenant_id' => $scenario['tenant']->id,
            'status' => PaymentStatus::Pending,
        ]);

        app(PaymentService::class)->reject($payment, $this->superAdmin(), 'Bukti tidak valid');

        Notification::assertSentTo($scenario['owner'], PaymentFailedNotification::class);
    }

    public function test_tenant_suspension_notifies_owner(): void
    {
        ['tenant' => $tenant, 'owner' => $owner] = $this->activeTenant();

        app(SubscriptionService::class)->suspend($tenant, 'Pelanggaran kebijakan.');

        Notification::assertSentTo($owner, TenantSuspendedNotification::class);
    }
}
