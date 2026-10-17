<?php

namespace Tests\Feature\Billing;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\SubscriptionStatus;
use App\Enums\TenantStatus;
use App\Models\Invoice;
use App\Services\InvoiceService;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SubscriptionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_first_payment_activates_subscription_for_configured_duration(): void
    {
        $scenario = $this->pendingTenant([], ['duration_days' => 30]);
        $invoice = Invoice::factory()->create([
            'tenant_id' => $scenario['tenant']->id,
            'subscription_id' => $scenario['subscription']->id,
            'status' => InvoiceStatus::Unpaid,
        ]);

        app(SubscriptionService::class)->activateFromInvoice($invoice);

        $subscription = $scenario['subscription']->fresh();
        $this->assertSame(SubscriptionStatus::Active, $subscription->status);
        $this->assertSame(TenantStatus::Active, $scenario['tenant']->fresh()->status);
        $this->assertSame(30, $subscription->daysRemaining());
    }

    public function test_renewal_before_expiry_extends_from_current_end_date(): void
    {
        ['tenant' => $tenant, 'subscription' => $subscription] = $this->activeTenant();

        $previousEnd = $subscription->ends_at->copy();

        $invoice = Invoice::factory()->create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'type' => InvoiceType::Renewal,
            'status' => InvoiceStatus::Unpaid,
        ]);

        app(SubscriptionService::class)->activateFromInvoice($invoice);

        $newEnd = $subscription->fresh()->ends_at;
        $this->assertTrue($newEnd->equalTo($previousEnd->copy()->addDays(30)));
    }

    public function test_renewal_after_expiry_starts_from_payment_date(): void
    {
        ['tenant' => $tenant, 'subscription' => $subscription] = $this->activeTenant();

        $subscription->forceFill([
            'status' => SubscriptionStatus::Expired,
            'starts_at' => now()->subDays(60),
            'ends_at' => now()->subDays(30),
        ])->save();

        $invoice = Invoice::factory()->create([
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'type' => InvoiceType::Renewal,
            'status' => InvoiceStatus::Unpaid,
        ]);

        app(SubscriptionService::class)->activateFromInvoice($invoice);

        $subscription->refresh();
        $this->assertSame(SubscriptionStatus::Active, $subscription->status);
        $this->assertTrue($subscription->ends_at->between(now()->addDays(29), now()->addDays(31)));
    }

    public function test_suspend_and_reactivate_tenant(): void
    {
        ['tenant' => $tenant, 'subscription' => $subscription] = $this->activeTenant();
        $service = app(SubscriptionService::class);

        $service->suspend($tenant, 'Pembayaran gagal.');

        $this->assertSame(TenantStatus::Suspended, $tenant->fresh()->status);
        $this->assertSame(SubscriptionStatus::Suspended, $subscription->fresh()->status);

        $service->reactivate($tenant->fresh());

        $this->assertSame(TenantStatus::Active, $tenant->fresh()->status);
        $this->assertSame(SubscriptionStatus::Active, $subscription->fresh()->status);
    }

    public function test_reactivating_after_end_date_expires_the_subscription(): void
    {
        ['tenant' => $tenant, 'subscription' => $subscription] = $this->activeTenant();
        $service = app(SubscriptionService::class);

        $service->suspend($tenant, 'Gagal bayar.');

        $subscription->forceFill(['ends_at' => now()->subDay()])->save();

        $service->reactivate($tenant->fresh());

        $this->assertSame(TenantStatus::Active, $tenant->fresh()->status);
        $this->assertSame(SubscriptionStatus::Expired, $subscription->fresh()->status);
    }

    public function test_admin_can_create_renewal_invoice_via_endpoint(): void
    {
        ['tenant' => $tenant, 'owner' => $owner, 'subscription' => $subscription] = $this->activeTenant();

        $this->actingAs($owner)
            ->post('/admin/subscription/renew')
            ->assertRedirect(route('admin.subscription.index'));

        $this->assertDatabaseHas('invoices', [
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
            'type' => InvoiceType::Renewal->value,
        ]);
    }

    public function test_invoice_price_is_snapshotted_from_subscription(): void
    {
        $scenario = $this->pendingTenant([], ['price' => 150000]);

        $invoice = app(InvoiceService::class)->createForSubscription(
            $scenario['tenant'],
            $scenario['subscription'],
        );

        $this->assertSame('150000.00', $invoice->amount);
    }
}
