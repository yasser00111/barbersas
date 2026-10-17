<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\PaymentFailed;
use App\Events\PaymentVerified;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentWebhookEvent;
use App\Models\User;
use App\Payments\GatewayWebhook;
use App\Payments\PaymentGatewayManager;
use App\Payments\PaymentSession;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        protected PaymentGatewayManager $gateways,
        protected SubscriptionService $subscriptions,
        protected AuditLogger $audit,
    ) {}

    /**
     * Record an uploaded manual-transfer proof. The invoice moves to "pending"
     * until a super admin verifies it.
     */
    public function submitManualProof(Invoice $invoice, UploadedFile $proof, ?float $amount = null): Payment
    {
        if ($invoice->status === InvoiceStatus::Paid) {
            throw ValidationException::withMessages([
                'proof' => 'Invoice ini sudah lunas.',
            ]);
        }

        return DB::transaction(function () use ($invoice, $proof, $amount) {
            $path = $proof->store('payment-proofs/'.$invoice->tenant_id, 'local');

            $payment = Payment::create([
                'invoice_id' => $invoice->getKey(),
                'tenant_id' => $invoice->tenant_id,
                'method' => PaymentMethod::Manual,
                'provider' => 'manual',
                'provider_reference' => $invoice->number.'-'.now()->timestamp,
                'amount' => $amount ?? $invoice->amount,
                'currency' => $invoice->currency,
                'status' => PaymentStatus::Pending,
                'proof_path' => $path,
            ]);

            $invoice->forceFill(['status' => InvoiceStatus::Pending])->save();

            $this->audit->log(
                event: 'payment.manual.submitted',
                auditable: $payment,
                description: 'Bukti transfer diunggah untuk invoice '.$invoice->number,
                tenantId: $invoice->tenant_id,
            );

            return $payment;
        });
    }

    public function approve(Payment $payment, User $admin, ?string $note = null): void
    {
        if ($payment->status === PaymentStatus::Paid) {
            return;
        }

        DB::transaction(function () use ($payment, $admin, $note) {
            $payment = Payment::withoutTenantScope()->lockForUpdate()->findOrFail($payment->getKey());
            $invoice = Invoice::withoutTenantScope()->findOrFail($payment->invoice_id);

            $payment->forceFill([
                'status' => PaymentStatus::Paid,
                'paid_at' => $payment->paid_at ?? now(),
                'verified_at' => now(),
                'verified_by' => $admin->getKey(),
                'rejection_reason' => null,
            ])->save();

            $this->rejectOtherPendingPayments($invoice, $payment->getKey());

            $this->audit->log(
                event: 'payment.manual.approved',
                auditable: $payment,
                description: 'Pembayaran diverifikasi oleh '.$admin->name.($note ? ': '.$note : ''),
                tenantId: $invoice->tenant_id,
            );

            $this->subscriptions->activateFromInvoice(
                $invoice,
                paidAt: $payment->paid_at ?? now(),
                reason: 'Pembayaran manual diverifikasi',
                performedBy: $admin->getKey(),
            );

            PaymentVerified::dispatch($payment->fresh());
        });
    }

    public function reject(Payment $payment, User $admin, string $reason): void
    {
        DB::transaction(function () use ($payment, $admin, $reason) {
            $payment = Payment::withoutTenantScope()->lockForUpdate()->findOrFail($payment->getKey());
            $invoice = Invoice::withoutTenantScope()->findOrFail($payment->invoice_id);

            $payment->forceFill([
                'status' => PaymentStatus::Rejected,
                'verified_at' => now(),
                'verified_by' => $admin->getKey(),
                'rejection_reason' => $reason,
            ])->save();

            if ($invoice->status === InvoiceStatus::Pending) {
                $invoice->forceFill(['status' => InvoiceStatus::Rejected])->save();
            }

            $this->audit->log(
                event: 'payment.manual.rejected',
                auditable: $payment,
                description: 'Pembayaran ditolak: '.$reason,
                tenantId: $invoice->tenant_id,
            );

            PaymentFailed::dispatch($payment->fresh());
        });
    }

    /**
     * Create a gateway checkout session. Any earlier pending online payment for
     * the invoice is expired so there is never more than one active attempt.
     */
    public function initiateGateway(Invoice $invoice, ?string $gatewayName = null): PaymentSession
    {
        if ($invoice->status === InvoiceStatus::Paid) {
            throw ValidationException::withMessages([
                'invoice' => 'Invoice ini sudah lunas.',
            ]);
        }

        $gateway = $gatewayName
            ? $this->gateways->driver($gatewayName)
            : $this->gateways->default();

        $session = $gateway->createCheckout($invoice);

        DB::transaction(function () use ($invoice, $session, $gateway) {
            Payment::withoutTenantScope()
                ->where('invoice_id', $invoice->getKey())
                ->where('method', PaymentMethod::Midtrans)
                ->where('status', PaymentStatus::Pending)
                ->update(['status' => PaymentStatus::Expired]);

            $existing = Payment::withoutTenantScope()
                ->where('invoice_id', $invoice->getKey())
                ->where('provider', $gateway->name())
                ->where('provider_reference', $session->reference)
                ->first();

            $attributes = [
                'invoice_id' => $invoice->getKey(),
                'tenant_id' => $invoice->tenant_id,
                'method' => PaymentMethod::Midtrans,
                'provider' => $gateway->name(),
                'provider_reference' => $session->reference,
                'amount' => $invoice->amount,
                'currency' => $invoice->currency,
                'status' => PaymentStatus::Pending,
                'provider_payload' => $session->raw,
            ];

            if ($existing) {
                $existing->forceFill($attributes)->save();
            } else {
                Payment::create($attributes);
            }
        });

        return $session;
    }

    /**
     * Handle an inbound gateway notification. Returns the persisted event.
     */
    public function handleWebhook(string $gatewayName, Request $request): PaymentWebhookEvent
    {
        $gateway = $this->gateways->driver($gatewayName);
        $signatureValid = $gateway->verifyWebhook($request);
        $webhook = $gateway->parseWebhook($request);

        $event = PaymentWebhookEvent::firstOrNew([
            'provider' => $webhook->provider,
            'event_id' => $webhook->eventId,
        ]);

        if ($event->exists) {
            return $event;
        }

        $event->fill([
            'event_type' => $webhook->type,
            'external_id' => $webhook->externalId,
            'signature_valid' => $signatureValid,
            'payload' => $webhook->raw,
            'status' => 'received',
        ]);

        if (! $signatureValid) {
            $event->status = 'rejected';
            $event->error = 'Signature tidak valid.';
            $event->save();

            return $event;
        }

        $event->save();

        try {
            $this->applyWebhook($webhook, $event);
        } catch (\Throwable $e) {
            report($e);
            $event->forceFill(['status' => 'failed', 'error' => $e->getMessage()])->save();
        }

        return $event->fresh();
    }

    protected function applyWebhook(GatewayWebhook $webhook, PaymentWebhookEvent $event): void
    {
        $orderId = $webhook->orderId;
        $invoice = Invoice::withoutTenantScope()->where('number', $orderId)->first();

        if (! $invoice && str_contains($orderId, '-attempt-')) {
            $baseNumber = explode('-attempt-', $orderId)[0];
            $invoice = Invoice::withoutTenantScope()->where('number', $baseNumber)->first();
        }

        if (! $invoice) {
            $event->forceFill(['status' => 'ignored', 'error' => 'Invoice tidak ditemukan.'])->save();

            return;
        }

        $event->invoice_id = $invoice->getKey();

        if (! $this->amountMatches($invoice, $webhook)) {
            $event->forceFill([
                'status' => 'failed',
                'error' => 'Nominal atau mata uang tidak cocok.',
            ])->save();

            return;
        }

        $payment = $this->upsertGatewayPayment($invoice, $webhook);
        $event->payment_id = $payment->getKey();

        match ($webhook->status) {
            'paid' => $this->applyPaidStatus($invoice, $payment, $webhook, $event),
            'pending' => $payment->forceFill(['status' => PaymentStatus::Pending])->save(),
            'failed' => $payment->forceFill(['status' => PaymentStatus::Failed])->save(),
            'expired' => $payment->forceFill(['status' => PaymentStatus::Expired])->save(),
            'refunded' => $this->applyRefund($payment, PaymentStatus::Refunded, $webhook),
            'chargeback' => $this->applyRefund($payment, PaymentStatus::Chargeback, $webhook),
            default => null,
        };

        $event->forceFill([
            'status' => 'processed',
            'processed_at' => now(),
        ])->save();
    }

    protected function applyPaidStatus(
        Invoice $invoice,
        Payment $payment,
        GatewayWebhook $webhook,
        PaymentWebhookEvent $event,
    ): void {
        if ($payment->status !== PaymentStatus::Paid) {
            $payment->forceFill([
                'status' => PaymentStatus::Paid,
                'paid_at' => $webhook->paidAt ?? now(),
                'verified_at' => now(),
            ])->save();
        }

        $this->subscriptions->activateFromInvoice(
            $invoice,
            paidAt: $webhook->paidAt ?? now(),
            reason: 'Pembayaran gateway terverifikasi',
            meta: ['provider' => $webhook->provider, 'transaction_id' => $webhook->externalId],
        );

        PaymentVerified::dispatch($payment->fresh());
    }

    protected function applyRefund(Payment $payment, PaymentStatus $status, GatewayWebhook $webhook): void
    {
        $payment->forceFill([
            'status' => $status,
            'provider_payload' => array_merge($payment->provider_payload ?? [], $webhook->raw),
        ])->save();

        $this->audit->log(
            event: 'payment.'.$status->value,
            auditable: $payment,
            description: 'Pembayaran '.$status->label().' dari provider '.$webhook->provider,
            tenantId: $payment->tenant_id,
        );
    }

    protected function upsertGatewayPayment(Invoice $invoice, GatewayWebhook $webhook): Payment
    {
        $payment = Payment::withoutTenantScope()
            ->where('invoice_id', $invoice->getKey())
            ->where('provider', $webhook->provider)
            ->where('provider_reference', $webhook->orderId)
            ->first();

        $attributes = [
            'invoice_id' => $invoice->getKey(),
            'tenant_id' => $invoice->tenant_id,
            'method' => PaymentMethod::Midtrans,
            'provider' => $webhook->provider,
            'provider_reference' => $webhook->externalId ?: $webhook->orderId,
            'amount' => $webhook->amount ?: $invoice->amount,
            'currency' => $webhook->currency,
            'provider_payload' => $webhook->raw,
        ];

        if ($payment) {
            $payment->forceFill($attributes)->save();

            return $payment;
        }

        return Payment::create(array_merge($attributes, ['status' => PaymentStatus::Pending]));
    }

    protected function amountMatches(Invoice $invoice, GatewayWebhook $webhook): bool
    {
        if (strtoupper($webhook->currency) !== strtoupper($invoice->currency)) {
            return false;
        }

        return abs((float) $webhook->amount - (float) $invoice->amount) < 0.01;
    }

    protected function rejectOtherPendingPayments(Invoice $invoice, int $keepId): void
    {
        Payment::withoutTenantScope()
            ->where('invoice_id', $invoice->getKey())
            ->where('id', '!=', $keepId)
            ->whereIn('status', [PaymentStatus::Pending->value])
            ->update(['status' => PaymentStatus::Rejected->value, 'rejection_reason' => 'Invoice sudah lunas.']);
    }
}
