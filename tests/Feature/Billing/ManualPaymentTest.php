<?php

namespace Tests\Feature\Billing;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\TenantStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ManualPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        Storage::fake('local');
    }

    protected function invoiceFor(array $scenario, InvoiceStatus $status = InvoiceStatus::Unpaid): Invoice
    {
        return Invoice::factory()->create([
            'tenant_id' => $scenario['tenant']->id,
            'subscription_id' => $scenario['subscription']->id,
            'amount' => $scenario['subscription']->price,
            'status' => $status,
        ]);
    }

    public function test_admin_can_upload_transfer_proof(): void
    {
        $scenario = $this->pendingTenant();
        $invoice = $this->invoiceFor($scenario, InvoiceStatus::Unpaid);

        $response = $this->actingAs($scenario['owner'])
            ->post("/admin/subscription/invoices/{$invoice->uuid}/proof", [
                'proof' => UploadedFile::fake()->create('bukti.pdf', 120, 'application/pdf'),
                'amount' => 99000,
            ]);

        $response->assertRedirect();

        $this->assertSame(InvoiceStatus::Pending, $invoice->fresh()->status);

        $payment = Payment::withoutTenantScope()->where('invoice_id', $invoice->id)->firstOrFail();
        $this->assertSame(PaymentStatus::Pending, $payment->status);
        Storage::disk('local')->assertExists($payment->proof_path);
    }

    public function test_super_admin_approval_activates_tenant_and_subscription(): void
    {
        $scenario = $this->pendingTenant();
        $invoice = $this->invoiceFor($scenario, InvoiceStatus::Pending);

        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'tenant_id' => $scenario['tenant']->id,
            'status' => PaymentStatus::Pending,
        ]);

        $response = $this->actingAs($this->superAdmin())
            ->post("/super-admin/payments/{$payment->uuid}/approve");

        $response->assertRedirect();

        $this->assertSame(PaymentStatus::Paid, $payment->fresh()->status);
        $this->assertSame(InvoiceStatus::Paid, $invoice->fresh()->status);
        $this->assertNotNull($payment->fresh()->verified_by);
        $this->assertSame(TenantStatus::Active, $scenario['tenant']->fresh()->status);

        $subscription = $scenario['subscription']->fresh();
        $this->assertSame(SubscriptionStatus::Active, $subscription->status);
        $this->assertNotNull($subscription->starts_at);
        $this->assertTrue($subscription->ends_at->between(now()->addDays(29), now()->addDays(31)));
    }

    public function test_approval_is_idempotent_and_does_not_extend_twice(): void
    {
        $scenario = $this->pendingTenant();
        $invoice = $this->invoiceFor($scenario, InvoiceStatus::Pending);

        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'tenant_id' => $scenario['tenant']->id,
            'status' => PaymentStatus::Pending,
        ]);

        $service = app(PaymentService::class);
        $superAdmin = $this->superAdmin();

        $service->approve($payment, $superAdmin);
        $firstEnd = $scenario['subscription']->fresh()->ends_at->copy();

        $service->approve($payment->fresh(), $superAdmin);
        $secondEnd = $scenario['subscription']->fresh()->ends_at->copy();

        $this->assertTrue($firstEnd->equalTo($secondEnd));
    }

    public function test_rejection_marks_payment_and_invoice_rejected(): void
    {
        $scenario = $this->pendingTenant();
        $invoice = $this->invoiceFor($scenario, InvoiceStatus::Pending);

        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'tenant_id' => $scenario['tenant']->id,
            'status' => PaymentStatus::Pending,
        ]);

        $this->actingAs($this->superAdmin())
            ->post("/super-admin/payments/{$payment->uuid}/reject", ['reason' => 'Bukti tidak jelas'])
            ->assertRedirect();

        $this->assertSame(PaymentStatus::Rejected, $payment->fresh()->status);
        $this->assertSame('Bukti tidak jelas', $payment->fresh()->rejection_reason);
        $this->assertSame(InvoiceStatus::Rejected, $invoice->fresh()->status);
    }

    public function test_tenant_admin_cannot_approve_payments(): void
    {
        $scenario = $this->pendingTenant();
        $invoice = $this->invoiceFor($scenario, InvoiceStatus::Pending);

        $payment = Payment::factory()->create([
            'invoice_id' => $invoice->id,
            'tenant_id' => $scenario['tenant']->id,
            'status' => PaymentStatus::Pending,
        ]);

        $this->actingAs($scenario['owner'])
            ->post("/super-admin/payments/{$payment->uuid}/approve")
            ->assertForbidden();

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
    }
}
