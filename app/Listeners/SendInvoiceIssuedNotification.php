<?php

namespace App\Listeners;

use App\Events\InvoiceIssued;
use App\Notifications\InvoiceIssuedNotification;
use App\Services\Notifier;

class SendInvoiceIssuedNotification
{
    public function __construct(protected Notifier $notifier) {}

    public function handle(InvoiceIssued $event): void
    {
        $invoice = $event->invoice;
        $tenant = $invoice->tenant;

        $user = $tenant?->owner ?? $tenant?->members()->with('user')->first()?->user;

        if (! $user) {
            return;
        }

        $this->notifier->send(
            $user,
            new InvoiceIssuedNotification($invoice, $user->name),
            'invoice_issued',
            'invoice-issued:'.$invoice->uuid,
            $invoice->tenant_id,
        );
    }
}
