<?php

namespace App\Services;

use App\Enums\InvoiceType;
use App\Events\InvoiceIssued;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Support\Str;

class InvoiceService
{
    public function generateNumber(): string
    {
        $prefix = 'INV-'.now()->format('Ym').'-';

        do {
            $number = $prefix.strtoupper(Str::random(6));
        } while (Invoice::withoutTenantScope()->where('number', $number)->exists());

        return $number;
    }

    /**
     * Create an invoice that snapshots the subscription price. Later changes to
     * the platform rate never affect invoices that already exist.
     */
    public function createForSubscription(
        Tenant $tenant,
        Subscription $subscription,
        InvoiceType $type = InvoiceType::New,
        ?int $dueInDays = null,
    ): Invoice {
        $dueAt = now()->addDays($dueInDays ?? (int) config('barbersaas.subscription.duration_days', 30));

        $invoice = Invoice::create([
            'tenant_id' => $tenant->getKey(),
            'subscription_id' => $subscription->getKey(),
            'number' => $this->generateNumber(),
            'type' => $type,
            'amount' => $subscription->price,
            'currency' => $subscription->currency,
            'plan_name' => $subscription->plan?->name,
            'duration_days' => $subscription->duration_days,
            'description' => $type === InvoiceType::New
                ? 'Aktivasi langganan '.$subscription->plan?->name
                : 'Perpanjangan langganan '.$subscription->plan?->name,
            'issued_at' => now(),
            'due_at' => $dueAt,
        ]);

        InvoiceIssued::dispatch($invoice);

        return $invoice;
    }
}
