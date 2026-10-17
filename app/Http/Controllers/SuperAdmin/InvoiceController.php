<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        $invoices = Invoice::withoutGlobalScopes()
            ->with(['tenant', 'payments'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search')->toString();
                $q->where('number', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('SuperAdmin/Invoices/Index', [
            'invoices' => $invoices->through(fn (Invoice $invoice) => [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'tenant' => $invoice->tenant?->name,
                'status' => $invoice->status->value,
                'status_label' => $invoice->status->label(),
                'amount' => (float) $invoice->amount,
                'currency' => $invoice->currency,
                'issued_at' => $invoice->issued_at?->toIso8601String(),
                'paid_at' => $invoice->paid_at?->toIso8601String(),
                'payments_count' => $invoice->payments->count(),
            ]),
            'filters' => [
                'status' => $request->string('status')->toString(),
                'search' => $request->string('search')->toString(),
            ],
        ]);
    }
}
