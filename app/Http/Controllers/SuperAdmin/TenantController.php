<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\SubscriptionEventType;
use App\Enums\SubscriptionStatus;
use App\Enums\TenantStatus;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\AuditLogger;
use App\Services\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class TenantController extends Controller
{
    public function __construct(
        protected SubscriptionService $subscriptions,
        protected AuditLogger $audit,
    ) {}

    public function index(Request $request): Response
    {
        $tenants = Tenant::query()
            ->with('currentSubscription.plan')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = $request->string('search')->toString();
                $q->where(fn ($w) => $w->where('name', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%"));
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('SuperAdmin/Tenants/Index', [
            'tenants' => $tenants->through(fn (Tenant $tenant) => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'status' => $tenant->status->value,
                'status_label' => $tenant->status->label(),
                'subscription_status' => $tenant->currentSubscription?->status->value,
                'subscription_ends_at' => $tenant->currentSubscription?->ends_at?->toIso8601String(),
                'plan' => $tenant->currentSubscription?->plan?->name,
                'created_at' => $tenant->created_at?->toIso8601String(),
            ]),
            'filters' => [
                'status' => $request->string('status')->toString(),
                'search' => $request->string('search')->toString(),
            ],
        ]);
    }

    public function show(Tenant $tenant): Response
    {
        $tenant->load(['owner', 'members.user', 'currentSubscription.plan']);

        return Inertia::render('SuperAdmin/Tenants/Show', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'status' => $tenant->status->value,
                'status_label' => $tenant->status->label(),
                'email' => $tenant->email,
                'phone' => $tenant->phone,
                'address' => $tenant->address,
                'city' => $tenant->city,
                'created_at' => $tenant->created_at?->toIso8601String(),
                'suspended_reason' => $tenant->suspended_reason,
                'owner' => $tenant->owner ? ['name' => $tenant->owner->name, 'email' => $tenant->owner->email] : null,
            ],
            'subscription' => $tenant->currentSubscription ? [
                'id' => $tenant->currentSubscription->id,
                'status' => $tenant->currentSubscription->status->value,
                'status_label' => $tenant->currentSubscription->status->label(),
                'starts_at' => $tenant->currentSubscription->starts_at?->toIso8601String(),
                'ends_at' => $tenant->currentSubscription->ends_at?->toIso8601String(),
                'plan' => $tenant->currentSubscription->plan?->name,
                'price' => (float) $tenant->currentSubscription->price,
            ] : null,
            'members' => $tenant->members->map(fn ($member) => [
                'id' => $member->id,
                'role' => $member->role,
                'name' => $member->user?->name,
                'email' => $member->user?->email,
            ]),
            'invoices' => $tenant->invoices()->latest()->get()->map(fn ($invoice) => [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'status' => $invoice->status->value,
                'status_label' => $invoice->status->label(),
                'amount' => (float) $invoice->amount,
                'issued_at' => $invoice->issued_at?->toIso8601String(),
                'paid_at' => $invoice->paid_at?->toIso8601String(),
            ]),
            'events' => $tenant->subscriptions()
                ->with('events')
                ->get()
                ->flatMap->events
                ->sortByDesc('created_at')
                ->take(20)
                ->map(fn ($event) => [
                    'id' => $event->id,
                    'type' => $event->type->value,
                    'from_status' => $event->from_status,
                    'to_status' => $event->to_status,
                    'reason' => $event->reason,
                    'created_at' => $event->created_at?->toIso8601String(),
                ])->values(),
        ]);
    }

    public function activate(Request $request, Tenant $tenant): RedirectResponse
    {
        $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        $subscription = $tenant->currentSubscription()->withoutGlobalScopes()->first();

        if ($subscription) {
            $subscription->forceFill(['status' => SubscriptionStatus::Active])->save();
        }

        if ($tenant->status !== TenantStatus::Active) {
            $this->subscriptions->reactivate($tenant, $request->user()->getKey());
        }

        if ($subscription && $subscription->ends_at === null) {
            $subscription->forceFill([
                'starts_at' => now(),
                'ends_at' => Carbon::now()->addDays((int) $subscription->duration_days),
            ])->save();
            $subscription->recordEvent(SubscriptionEventType::Activated, [
                'to_status' => SubscriptionStatus::Active->value,
                'reason' => $request->input('reason', 'Aktivasi manual oleh Super Admin'),
                'performed_by' => $request->user()->getKey(),
            ]);
        }

        $this->audit->log('tenant.activated_manually', $tenant, 'Aktivasi manual oleh Super Admin', [], [
            'reason' => $request->input('reason'),
        ], $tenant->getKey());

        return back()->with('success', 'Tenant berhasil diaktifkan.');
    }

    public function suspend(Request $request, Tenant $tenant): RedirectResponse
    {
        $request->validate(['reason' => ['required', 'string', 'max:500']]);

        $this->subscriptions->suspend($tenant, $request->string('reason')->toString(), $request->user()->getKey());

        return back()->with('success', 'Tenant berhasil ditangguhkan.');
    }

    public function reactivate(Request $request, Tenant $tenant): RedirectResponse
    {
        $this->subscriptions->reactivate($tenant, $request->user()->getKey());

        return back()->with('success', 'Tenant berhasil diaktifkan kembali.');
    }

    public function archive(Request $request, Tenant $tenant): RedirectResponse
    {
        $tenant->forceFill(['status' => TenantStatus::Archived])->save();

        $this->audit->log('tenant.archived', $tenant, 'Tenant diarsipkan.', [], [], $tenant->getKey());

        return back()->with('success', 'Tenant diarsipkan.');
    }
}
