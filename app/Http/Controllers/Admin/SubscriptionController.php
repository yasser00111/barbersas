<?php

namespace App\Http\Controllers\Admin;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Support\TenantSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Billing pages for the tenant admin. These routes are intentionally NOT behind
 * the "tenant.active" middleware: a tenant must always be able to view its
 * invoices, subscription status and pay to reactivate.
 */
class SubscriptionController extends Controller
{
    public function __construct(
        protected PaymentService $payments,
        protected InvoiceService $invoices,
    ) {}

    public function index(Request $request): Response
    {
        $tenant = $request->user()->tenant;
        $subscription = $tenant->currentSubscription()->with('plan')->first();

        $invoices = $tenant->invoices()
            ->with(['payments'])
            ->latest()
            ->get()
            ->map(fn (Invoice $invoice) => [
                'id' => $invoice->id,
                'uuid' => $invoice->uuid,
                'number' => $invoice->number,
                'type' => $invoice->type instanceof InvoiceType ? $invoice->type->value : (string) $invoice->type,
                'status' => $invoice->status->value,
                'status_label' => $invoice->status->label(),
                'status_color' => $invoice->status->color(),
                'amount' => (float) $invoice->amount,
                'currency' => $invoice->currency,
                'plan_name' => $invoice->plan_name,
                'duration_days' => $invoice->duration_days,
                'description' => $invoice->description,
                'issued_at' => $invoice->issued_at?->toIso8601String(),
                'due_at' => $invoice->due_at?->toIso8601String(),
                'paid_at' => $invoice->paid_at?->toIso8601String(),
                'is_payable' => $invoice->isPayable(),
                'has_pending_payment' => $invoice->payments->contains(fn ($p) => $p->status === \App\Enums\PaymentStatus::Pending && $p->method === \App\Enums\PaymentMethod::Manual),
                'payments' => $invoice->payments->map(fn ($payment) => [
                    'id' => $payment->id,
                    'method' => $payment->method->value,
                    'method_label' => $payment->method->label(),
                    'status' => $payment->status->value,
                    'status_label' => $payment->status->label(),
                    'rejection_reason' => $payment->rejection_reason,
                    'created_at' => $payment->created_at?->toIso8601String(),
                ]),
            ]);

        $settings = TenantSettings::for($tenant);

        return Inertia::render('Admin/Subscription/Index', [
            'subscription' => $subscription ? [
                'id' => $subscription->id,
                'status' => $subscription->status->value,
                'status_label' => $subscription->status->label(),
                'starts_at' => $subscription->starts_at?->toIso8601String(),
                'ends_at' => $subscription->ends_at?->toIso8601String(),
                'days_remaining' => $subscription->daysRemaining(),
                'price' => (float) $subscription->price,
                'currency' => $subscription->currency,
                'duration_days' => $subscription->duration_days,
                'plan' => $subscription->plan ? [
                    'id' => $subscription->plan->id,
                    'name' => $subscription->plan->name,
                ] : null,
            ] : null,
            'invoices' => $invoices,
            'paymentInstructions' => [
                'bank_name' => $settings->get('payment_bank_name'),
                'bank_account' => $settings->get('payment_bank_account'),
                'account_name' => $settings->get('payment_account_name'),
                'instructions' => $settings->get('payment_instructions'),
            ],
            'gateway' => config('barbersaas.payment.default'),
            'gatewayEnabled' => config('barbersaas.payment.default') !== 'manual',
        ]);
    }

    public function uploadProof(Request $request, Invoice $invoice): RedirectResponse
    {
        $this->authorize('pay', $invoice);

        if ($invoice->status === InvoiceStatus::Paid) {
            return back()->with('error', 'Invoice ini sudah lunas.');
        }

        $request->validate([
            'proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
            'amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $this->payments->submitManualProof(
            $invoice,
            $request->file('proof'),
            $request->filled('amount') ? (float) $request->input('amount') : null,
        );

        return back()->with('success', 'Bukti transfer dikirim. Menunggu verifikasi Super Admin.');
    }

    public function pay(Request $request, Invoice $invoice): \Symfony\Component\HttpFoundation\Response
    {
        $this->authorize('pay', $invoice);

        if (! $invoice->isPayable()) {
            return back()->with('error', 'Invoice ini tidak dapat dibayar saat ini.');
        }

        if (config('barbersaas.payment.default') === 'manual') {
            return back()->with('error', 'Pembayaran online belum diaktifkan. Silakan unggah bukti transfer.');
        }

        try {
            $session = $this->payments->initiateGateway($invoice);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $msg = $e->validator->errors()->first('gateway') ?: $e->getMessage();

            return back()->with('error', $msg);
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal memproses pembayaran: '.$e->getMessage());
        }

        if ($session->redirectUrl) {
            return Inertia::location($session->redirectUrl);
        }

        return back()->with('success', 'Sesi pembayaran dibuat.');
    }

    public function renew(Request $request): RedirectResponse
    {
        $tenant = $request->user()->tenant;
        $subscription = $tenant->currentSubscription()->with('plan')->first();

        if (! $subscription) {
            return back()->with('error', 'Tidak ada langganan untuk diperpanjang.');
        }

        if ($subscription->plan === null) {
            return back()->with('error', 'Paket langganan tidak ditemukan.');
        }

        $hasUnpaid = $tenant->invoices()
            ->whereIn('status', [InvoiceStatus::Unpaid->value, InvoiceStatus::Pending->value])
            ->exists();

        if ($hasUnpaid) {
            return back()->with('error', 'Selesaikan invoice yang belum dibayar terlebih dahulu.');
        }

        $this->invoices->createForSubscription($tenant, $subscription, InvoiceType::Renewal);

        return redirect()
            ->route('admin.subscription.index')
            ->with('success', 'Invoice perpanjangan dibuat. Silakan selesaikan pembayaran.');
    }
}
