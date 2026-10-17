<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\SubscriptionStatus;
use App\Enums\TenantStatus;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $tenantCounts = Tenant::query()
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $revenue = Payment::query()
            ->where('status', PaymentStatus::Paid)
            ->selectRaw('SUM(amount) as total, SUBSTR(paid_at, 1, 7) as period')
            ->whereNotNull('paid_at')
            ->groupBy('period')
            ->orderBy('period', 'desc')
            ->limit(12)
            ->get();

        return Inertia::render('SuperAdmin/Dashboard', [
            'stats' => [
                'tenants_total' => Tenant::count(),
                'tenants_active' => (int) ($tenantCounts[TenantStatus::Active->value] ?? 0),
                'tenants_pending' => (int) ($tenantCounts[TenantStatus::Pending->value] ?? 0),
                'tenants_suspended' => (int) ($tenantCounts[TenantStatus::Suspended->value] ?? 0),
                'subscriptions_active' => Subscription::withoutGlobalScopes()->where('status', SubscriptionStatus::Active)->count(),
                'subscriptions_expired' => Subscription::withoutGlobalScopes()->where('status', SubscriptionStatus::Expired)->count(),
                'invoices_unpaid' => Invoice::withoutGlobalScopes()->whereIn('status', [InvoiceStatus::Unpaid, InvoiceStatus::Pending])->count(),
                'payments_pending' => Payment::withoutGlobalScopes()->where('status', PaymentStatus::Pending)->count(),
                'revenue_month' => (float) Payment::withoutGlobalScopes()
                    ->where('status', PaymentStatus::Paid)
                    ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
                    ->sum('amount'),
                'revenue_year' => (float) Payment::withoutGlobalScopes()
                    ->where('status', PaymentStatus::Paid)
                    ->whereYear('paid_at', now()->year)
                    ->sum('amount'),
                'revenue_total' => (float) Payment::withoutGlobalScopes()->where('status', PaymentStatus::Paid)->sum('amount'),
            ],
            'recentTenants' => Tenant::query()->latest()->limit(8)->get()->map(fn (Tenant $tenant) => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'status' => $tenant->status->value,
                'status_label' => $tenant->status->label(),
                'created_at' => $tenant->created_at?->toIso8601String(),
            ]),
            'pendingPayments' => Payment::withoutGlobalScopes()
                ->where('status', PaymentStatus::Pending)
                ->with('invoice')
                ->latest()
                ->limit(8)
                ->get()
                ->map(fn (Payment $payment) => [
                    'id' => $payment->id,
                    'amount' => (float) $payment->amount,
                    'method' => $payment->method->value,
                    'invoice_number' => $payment->invoice?->number,
                    'created_at' => $payment->created_at?->toIso8601String(),
                ]),
            'revenueSeries' => $revenue->map(fn ($row) => [
                'period' => $row->period,
                'total' => (float) $row->total,
            ])->values(),
        ]);
    }
}
