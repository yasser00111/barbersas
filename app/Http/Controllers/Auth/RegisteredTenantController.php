<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterTenantRequest;
use App\Models\SubscriptionPlan;
use App\Services\TenantProvisioningService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredTenantController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register', [
            'plans' => SubscriptionPlan::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->map(fn ($plan) => [
                    'id' => $plan->id,
                    'name' => $plan->name,
                    'price' => (float) $plan->price,
                    'currency' => $plan->currency,
                    'duration_days' => $plan->duration_days,
                    'features' => $plan->features ?? [],
                ]),
        ]);
    }

    public function store(
        RegisterTenantRequest $request,
        TenantProvisioningService $provision,
    ): RedirectResponse {
        $plan = SubscriptionPlan::query()
            ->where('is_active', true)
            ->findOrFail($request->integer('plan_id'));

        $result = $provision->register(
            owner: [
                'name' => $request->string('owner_name')->toString(),
                'email' => $request->string('email')->toString(),
                'phone' => $request->string('phone')->toString(),
                'password' => $request->string('password')->toString(),
            ],
            business: [
                'name' => $request->string('business_name')->toString(),
                'phone' => $request->input('business_phone'),
                'city' => $request->input('city'),
            ],
            plan: $plan,
        );

        event(new Registered($result['user']));

        Auth::login($result['user']);

        return redirect()
            ->route('admin.subscription.index')
            ->with('success', 'Pendaftaran berhasil. Silakan verifikasi email dan selesaikan pembayaran untuk mengaktifkan barbershop Anda.');
    }
}
