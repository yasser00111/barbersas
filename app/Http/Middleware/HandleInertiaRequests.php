<?php

namespace App\Http\Middleware;

use App\Support\TenantContext;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $tenant = app(TenantContext::class)->get();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'role' => $user->role->value,
                    'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                    'tenant_id' => $user->tenant_id,
                ] : null,
            ],
            'tenant' => $tenant ? [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'status' => $tenant->status->value,
                'logo_path' => $tenant->logo_path,
                'phone' => $tenant->phone,
                'address' => $tenant->address,
                'public_url' => $tenant->publicUrl(),
                'subscription' => $tenant->currentSubscription ? [
                    'id' => $tenant->currentSubscription->id,
                    'status' => $tenant->currentSubscription->status->value,
                    'starts_at' => $tenant->currentSubscription->starts_at?->toIso8601String(),
                    'ends_at' => $tenant->currentSubscription->ends_at?->toIso8601String(),
                    'days_remaining' => $tenant->currentSubscription->daysRemaining(),
                    'plan' => $tenant->currentSubscription->plan ? [
                        'id' => $tenant->currentSubscription->plan->id,
                        'name' => $tenant->currentSubscription->plan->name,
                    ] : null,
                ] : null,
            ] : null,
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
