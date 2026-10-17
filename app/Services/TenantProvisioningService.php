<?php

namespace App\Services;

use App\Enums\Role;
use App\Enums\SubscriptionStatus;
use App\Enums\TenantStatus;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantMember;
use App\Models\User;
use App\Support\TenantSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantProvisioningService
{
    public function __construct(
        protected SubscriptionService $subscriptions,
        protected AuditLogger $audit,
    ) {}

    /**
     * Register a brand new tenant together with its owner account, default
     * business hours, settings and first (pending) subscription + invoice.
     *
     * @param  array{name: string, email: string, phone: string, password: string}  $owner
     * @param  array{name: string, phone?: ?string, city?: ?string}  $business
     */
    public function register(array $owner, array $business, SubscriptionPlan $plan): array
    {
        $result = DB::transaction(function () use ($owner, $business, $plan) {
            $tenant = Tenant::create([
                'name' => $business['name'],
                'slug' => $this->uniqueSlug($business['name']),
                'email' => $owner['email'],
                'phone' => $business['phone'] ?? $owner['phone'],
                'city' => $business['city'] ?? null,
                'status' => TenantStatus::Pending,
                'timezone' => 'Asia/Jakarta',
            ]);

            $user = User::create([
                'name' => $owner['name'],
                'email' => $owner['email'],
                'phone' => $owner['phone'],
                'password' => $owner['password'],
                'role' => Role::TenantAdmin,
                'tenant_id' => $tenant->getKey(),
            ]);

            $tenant->forceFill(['owner_user_id' => $user->getKey()])->save();

            TenantMember::create([
                'tenant_id' => $tenant->getKey(),
                'user_id' => $user->getKey(),
                'role' => 'owner',
                'status' => 'active',
                'joined_at' => now(),
            ]);

            $this->createDefaultBusinessHours($tenant);
            $this->createDefaultSettings($tenant);

            $subscription = $this->subscriptions->startInitial($tenant, $plan);

            $this->audit->log(
                event: 'tenant.registered',
                auditable: $tenant,
                description: 'Pendaftaran tenant baru: '.$tenant->name,
                tenantId: $tenant->getKey(),
            );

            return compact('tenant', 'user', 'subscription');
        });

        return $result;
    }

    protected function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'barber';
        $slug = $base;
        $i = 1;

        while (Tenant::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    protected function createDefaultBusinessHours(Tenant $tenant): void
    {
        foreach (range(0, 6) as $day) {
            $isWeekday = $day >= 1 && $day <= 6;

            $tenant->businessHours()->create([
                'day_of_week' => $day,
                'is_open' => $isWeekday,
                'open_time' => $isWeekday ? '09:00' : null,
                'close_time' => $isWeekday ? '21:00' : null,
                'break_start' => $isWeekday ? '12:00' : null,
                'break_end' => $isWeekday ? '13:00' : null,
            ]);
        }
    }

    protected function createDefaultSettings(Tenant $tenant): void
    {
        $settings = TenantSettings::for($tenant);

        foreach (TenantSettings::defaults() as $key => $value) {
            $settings->set($key, $value);
        }
    }

    public function pendingInvoice(Tenant $tenant): ?\App\Models\Invoice
    {
        $subscription = $tenant->subscriptions()
            ->where('status', SubscriptionStatus::Pending)
            ->latest()
            ->first();

        return $subscription?->invoices()->latest()->first();
    }
}
