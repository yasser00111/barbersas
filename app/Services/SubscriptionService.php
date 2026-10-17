<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\SubscriptionEventType;
use App\Enums\SubscriptionStatus;
use App\Enums\TenantStatus;
use App\Events\SubscriptionActivated;
use App\Events\SubscriptionExpired;
use App\Events\SubscriptionRenewed;
use App\Events\TenantSuspended;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    public function __construct(
        protected InvoiceService $invoices,
        protected AuditLogger $audit,
    ) {}

    /**
     * Create the first (pending) subscription plus its invoice at registration.
     * The tenant stays "pending" until a payment is verified.
     */
    public function startInitial(Tenant $tenant, SubscriptionPlan $plan): Subscription
    {
        return DB::transaction(function () use ($tenant, $plan) {
            $subscription = Subscription::create([
                'tenant_id' => $tenant->getKey(),
                'subscription_plan_id' => $plan->getKey(),
                'status' => SubscriptionStatus::Pending,
                'price' => $plan->price,
                'currency' => $plan->currency,
                'duration_days' => $plan->duration_days,
            ]);

            $subscription->recordEvent(SubscriptionEventType::Created, [
                'to_status' => SubscriptionStatus::Pending->value,
                'meta' => ['plan' => $plan->name, 'price' => (string) $plan->price],
            ]);

            $this->invoices->createForSubscription($tenant, $subscription, InvoiceType::New);

            return $subscription;
        });
    }

    /**
     * Activate or renew a subscription because its invoice has been paid.
     *
     * Idempotent: a paid invoice is never applied twice, so retried webhooks or
     * double approvals cannot extend a subscription more than once.
     *
     * @param  array<string, mixed>  $meta
     */
    public function activateFromInvoice(
        Invoice $invoice,
        ?Carbon $paidAt = null,
        ?string $reason = null,
        ?int $performedBy = null,
        array $meta = [],
    ): Subscription {
        return DB::transaction(function () use ($invoice, $paidAt, $reason, $performedBy, $meta) {
            $invoice = Invoice::withoutTenantScope()->lockForUpdate()->findOrFail($invoice->getKey());

            if ($invoice->status === InvoiceStatus::Paid) {
                return Subscription::withoutTenantScope()->findOrFail($invoice->subscription_id);
            }

            $subscription = Subscription::withoutTenantScope()
                ->lockForUpdate()
                ->findOrFail($invoice->subscription_id);

            $tenant = $invoice->tenant()->withoutGlobalScopes()->first();

            $start = ($paidAt ?? now())->copy();
            $fromStatus = $subscription->status;

            $isExtension = $subscription->status === SubscriptionStatus::Active
                && $subscription->ends_at !== null
                && $subscription->ends_at->isFuture();

            if ($isExtension) {
                $newStart = $subscription->starts_at;
                $newEnd = $subscription->ends_at->copy()->addDays((int) $subscription->duration_days);
                $eventType = $invoice->type === InvoiceType::Renewal
                    ? SubscriptionEventType::Renewed
                    : SubscriptionEventType::Expanded;
            } else {
                $newStart = $start;
                $newEnd = $start->copy()->addDays((int) $subscription->duration_days);
                $eventType = SubscriptionEventType::Activated;
            }

            $invoice->forceFill([
                'status' => InvoiceStatus::Paid,
                'paid_at' => $start,
            ])->save();

            $subscription->forceFill([
                'status' => SubscriptionStatus::Active,
                'starts_at' => $newStart,
                'ends_at' => $newEnd,
                'cancelled_at' => null,
                'cancel_reason' => null,
            ])->save();

            if ($tenant && $tenant->status !== TenantStatus::Active) {
                $tenant->forceFill([
                    'status' => TenantStatus::Active,
                    'activated_at' => $tenant->activated_at ?? now(),
                    'suspended_at' => null,
                    'suspended_reason' => null,
                ])->save();
            }

            $subscription->recordEvent($eventType, [
                'from_status' => $fromStatus->value,
                'to_status' => SubscriptionStatus::Active->value,
                'reason' => $reason,
                'performed_by' => $performedBy,
                'meta' => array_merge([
                    'invoice' => $invoice->number,
                    'starts_at' => $newStart?->toIso8601String(),
                    'ends_at' => $newEnd?->toIso8601String(),
                ], $meta),
            ]);

            $this->audit->log(
                event: $isExtension ? 'subscription.renewed' : 'subscription.activated',
                auditable: $subscription,
                description: 'Langganan diaktifkan untuk invoice '.$invoice->number,
                new: [
                    'status' => SubscriptionStatus::Active->value,
                    'starts_at' => (string) $newStart,
                    'ends_at' => (string) $newEnd,
                ],
                tenantId: $subscription->tenant_id,
            );

            if ($isExtension) {
                SubscriptionRenewed::dispatch($subscription);
            } else {
                SubscriptionActivated::dispatch($subscription);
            }

            return $subscription;
        });
    }

    public function expire(Subscription $subscription): void
    {
        if ($subscription->status !== SubscriptionStatus::Active) {
            return;
        }

        DB::transaction(function () use ($subscription) {
            $from = $subscription->status;

            $subscription->forceFill(['status' => SubscriptionStatus::Expired])->save();

            $subscription->recordEvent(SubscriptionEventType::Expired, [
                'from_status' => $from->value,
                'to_status' => SubscriptionStatus::Expired->value,
                'reason' => 'Masa langganan berakhir otomatis.',
            ]);

            $this->audit->log(
                event: 'subscription.expired',
                auditable: $subscription,
                description: 'Langganan kedaluwarsa.',
                tenantId: $subscription->tenant_id,
            );
        });

        SubscriptionExpired::dispatch($subscription);
    }

    public function suspend(Tenant $tenant, string $reason, ?int $performedBy = null): void
    {
        DB::transaction(function () use ($tenant, $reason, $performedBy) {
            $tenant->forceFill([
                'status' => TenantStatus::Suspended,
                'suspended_at' => now(),
                'suspended_reason' => $reason,
            ])->save();

            $subscription = $tenant->currentSubscription()->withoutGlobalScopes()->first();

            if ($subscription && $subscription->status === SubscriptionStatus::Active) {
                $subscription->forceFill(['status' => SubscriptionStatus::Suspended])->save();
                $subscription->recordEvent(SubscriptionEventType::Suspended, [
                    'from_status' => SubscriptionStatus::Active->value,
                    'to_status' => SubscriptionStatus::Suspended->value,
                    'reason' => $reason,
                    'performed_by' => $performedBy,
                ]);
            }

            $this->audit->log(
                event: 'tenant.suspended',
                auditable: $tenant,
                description: 'Tenant ditangguhkan: '.$reason,
                tenantId: $tenant->getKey(),
            );
        });

        TenantSuspended::dispatch($tenant, $reason);
    }

    public function reactivate(Tenant $tenant, ?int $performedBy = null): void
    {
        DB::transaction(function () use ($tenant, $performedBy) {
            $tenant->forceFill([
                'status' => TenantStatus::Active,
                'suspended_at' => null,
                'suspended_reason' => null,
                'activated_at' => $tenant->activated_at ?? now(),
            ])->save();

            $subscription = $tenant->currentSubscription()->withoutGlobalScopes()->first();

            if ($subscription && $subscription->status === SubscriptionStatus::Suspended) {
                $stillValid = $subscription->ends_at !== null && $subscription->ends_at->isFuture();
                $to = $stillValid ? SubscriptionStatus::Active : SubscriptionStatus::Expired;

                $subscription->forceFill(['status' => $to])->save();
                $subscription->recordEvent(SubscriptionEventType::Reactivated, [
                    'from_status' => SubscriptionStatus::Suspended->value,
                    'to_status' => $to->value,
                    'performed_by' => $performedBy,
                ]);
            }

            $this->audit->log(
                event: 'tenant.reactivated',
                auditable: $tenant,
                description: 'Tenant diaktifkan kembali.',
                tenantId: $tenant->getKey(),
            );
        });
    }
}
