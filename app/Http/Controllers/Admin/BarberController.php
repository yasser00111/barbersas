<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BarberRequest;
use App\Models\Barber;
use App\Models\Service;
use App\Models\TenantMember;
use App\Models\User;
use App\Notifications\StaffAccountInvitation;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;

class BarberController extends Controller
{
    public function __construct(protected AuditLogger $audit) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Barbers/Index', [
            'barbers' => Barber::query()
                ->with(['services:id', 'user:id,name,email'])
                ->orderBy('sort_order')
                ->get()
                ->map(fn (Barber $barber) => [
                    'id' => $barber->id,
                    'name' => $barber->name,
                    'phone' => $barber->phone,
                    'email' => $barber->email,
                    'bio' => $barber->bio,
                    'photo_path' => $barber->photo_path,
                    'is_active' => $barber->is_active,
                    'service_ids' => $barber->services->pluck('id'),
                    'has_account' => $barber->user_id !== null,
                    'account_email' => $barber->user?->email,
                ]),
            'services' => Service::query()->orderBy('sort_order')->get(['id', 'name']),
        ]);
    }

    public function store(BarberRequest $request): RedirectResponse
    {
        $data = $request->safe()->except(['photo', 'service_ids', 'create_account', 'account_email', 'account_password']);

        DB::transaction(function () use ($request, $data) {
            $tenantId = $request->user()->tenant_id;

            $barber = Barber::create(array_merge($data, ['tenant_id' => $tenantId]));

            if ($request->hasFile('photo')) {
                $barber->update(['photo_path' => $request->file('photo')->store('barbers/'.$tenantId, 'public')]);
            }

            $barber->services()->sync($request->input('service_ids', []));

            if ($request->boolean('create_account')) {
                $this->createAccount($barber, $request->input('account_email'), $request->input('account_password'));
            }

            $this->audit->log('barber.created', $barber, 'Barber ditambahkan: '.$barber->name, [], $barber->only(['name', 'is_active']));
        });

        return back()->with('success', 'Barber berhasil ditambahkan.');
    }

    public function update(BarberRequest $request, Barber $barber): RedirectResponse
    {
        $this->authorize('update', $barber->tenant);

        $data = $request->safe()->except(['photo', 'service_ids', 'create_account', 'account_email', 'account_password']);

        DB::transaction(function () use ($request, $barber, $data) {
            $old = $barber->only(['name', 'is_active']);

            $barber->update($data);

            if ($request->hasFile('photo')) {
                $barber->update(['photo_path' => $request->file('photo')->store('barbers/'.$barber->tenant_id, 'public')]);
            }

            $barber->services()->sync($request->input('service_ids', []));

            if ($request->boolean('create_account') && $barber->user_id === null) {
                $this->createAccount($barber, $request->input('account_email'), $request->input('account_password'));
            }

            $this->audit->log('barber.updated', $barber, 'Barber diperbarui: '.$barber->name, $old, $barber->only(['name', 'is_active']));
        });

        return back()->with('success', 'Barber berhasil diperbarui.');
    }

    public function destroy(Barber $barber): RedirectResponse
    {
        $this->authorize('update', $barber->tenant);

        if ($barber->user_id) {
            User::query()->whereKey($barber->user_id)->update(['status' => 'suspended']);
        }

        $barber->delete();

        $this->audit->log('barber.deleted', null, 'Barber dihapus: '.$barber->name);

        return back()->with('success', 'Barber dihapus.');
    }

    protected function createAccount(Barber $barber, string $email, string $password): void
    {
        $user = User::create([
            'name' => $barber->name,
            'email' => $email,
            'phone' => $barber->phone,
            'password' => Hash::make($password),
            'role' => Role::Barber,
            'tenant_id' => $barber->tenant_id,
        ]);

        TenantMember::create([
            'tenant_id' => $barber->tenant_id,
            'user_id' => $user->getKey(),
            'role' => 'staff',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $barber->update(['user_id' => $user->getKey()]);

        // Never email a permanent password: send a one-time, expiring link so
        // the barber sets their own password.
        $token = Password::broker()->createToken($user);

        $user->notify(new StaffAccountInvitation(
            staffName: $user->name,
            businessName: (string) $barber->tenant?->name,
            setPasswordUrl: route('password.reset', ['token' => $token, 'email' => $user->email]),
        ));
    }
}
