<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function __construct(protected PaymentService $payments) {}

    public function index(Request $request): Response
    {
        $payments = Payment::withoutGlobalScopes()
            ->with(['invoice', 'tenant'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('method'), fn ($q) => $q->where('method', $request->string('method')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('SuperAdmin/Payments/Index', [
            'payments' => $payments->through(fn (Payment $payment) => [
                'id' => $payment->id,
                'uuid' => $payment->uuid,
                'invoice_number' => $payment->invoice?->number,
                'tenant' => $payment->tenant?->name,
                'method' => $payment->method->value,
                'method_label' => $payment->method->label(),
                'status' => $payment->status->value,
                'status_label' => $payment->status->label(),
                'amount' => (float) $payment->amount,
                'has_proof' => (bool) $payment->proof_path,
                'proof_url' => $payment->proof_path ? route('super-admin.payments.proof', $payment->uuid) : null,
                'rejection_reason' => $payment->rejection_reason,
                'created_at' => $payment->created_at?->toIso8601String(),
            ]),
            'filters' => [
                'status' => $request->string('status')->toString(),
                'method' => $request->string('method')->toString(),
            ],
        ]);
    }

    public function proof(Payment $payment): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        abort_if(! $payment->proof_path || ! Storage::disk('local')->exists($payment->proof_path), 404);

        return Storage::disk('local')->download($payment->proof_path);
    }

    public function approve(Request $request, Payment $payment): RedirectResponse
    {
        $request->validate(['note' => ['nullable', 'string', 'max:500']]);

        $this->payments->approve($payment, $request->user(), $request->input('note'));

        return back()->with('success', 'Pembayaran diverifikasi dan langganan diaktifkan.');
    }

    public function reject(Request $request, Payment $payment): RedirectResponse
    {
        $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $this->payments->reject($payment, $request->user(), $request->string('reason')->toString());

        return back()->with('success', 'Pembayaran ditolak.');
    }
}
