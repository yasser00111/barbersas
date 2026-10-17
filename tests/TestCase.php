<?php

namespace Tests;

use App\Enums\Role;
use App\Enums\SubscriptionStatus;
use App\Enums\TenantStatus;
use App\Enums\UserStatus;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantMember;
use App\Models\User;
use App\Support\TenantContext;
use App\Support\TenantSettings;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    protected function plan(array $attributes = []): SubscriptionPlan
    {
        return SubscriptionPlan::factory()->create($attributes);
    }

    protected function superAdmin(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => Role::SuperAdmin,
            'status' => UserStatus::Active,
            'tenant_id' => null,
        ], $attributes));
    }

    /**
     * Create a barber user (with matching Barber profile) that is an active
     * member of the given tenant.
     *
     * @return array{user: User, barber: \App\Models\Barber}
     */
    protected function barberFor(Tenant $tenant, array $userAttributes = []): array
    {
        $user = User::factory()->create(array_merge([
            'role' => Role::Barber,
            'status' => UserStatus::Active,
            'tenant_id' => $tenant->getKey(),
        ], $userAttributes));

        $barber = \App\Models\Barber::factory()->create([
            'tenant_id' => $tenant->getKey(),
            'user_id' => $user->getKey(),
        ]);

        TenantMember::create([
            'tenant_id' => $tenant->getKey(),
            'user_id' => $user->getKey(),
            'role' => 'barber',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        return compact('user', 'barber');
    }

    protected function setTenantContext(Tenant $tenant): void
    {
        app(TenantContext::class)->set($tenant);
    }

    /**
     * Create a tenant with an owner user and (optionally) an active subscription.
     *
     * @param  array<string, mixed>  $tenantAttributes
     * @return array{tenant: Tenant, owner: User, plan: SubscriptionPlan, subscription: Subscription|null}
     */
    protected function activeTenant(array $tenantAttributes = [], bool $withSubscription = true, array $planAttributes = []): array
    {
        ['tenant' => $tenant, 'owner' => $owner, 'plan' => $plan] = $this->pendingTenant(
            array_merge(['status' => TenantStatus::Active, 'activated_at' => now()], $tenantAttributes),
            $planAttributes,
        );

        $subscription = null;

        if ($withSubscription) {
            $subscription = Subscription::create([
                'tenant_id' => $tenant->getKey(),
                'subscription_plan_id' => $plan->getKey(),
                'status' => SubscriptionStatus::Active,
                'price' => $plan->price,
                'currency' => $plan->currency,
                'duration_days' => 30,
                'starts_at' => now(),
                'ends_at' => now()->addDays(30),
            ]);
        }

        return compact('tenant', 'owner', 'plan', 'subscription');
    }

    /**
     * Create a pending tenant (owner + membership + hours + settings) with a
     * pending subscription, mirroring what registration produces.
     *
     * @param  array<string, mixed>  $tenantAttributes
     * @return array{tenant: Tenant, owner: User, plan: SubscriptionPlan, subscription: Subscription}
     */
    protected function pendingTenant(array $tenantAttributes = [], array $planAttributes = []): array
    {
        $tenant = Tenant::factory()->create(array_merge([
            'status' => TenantStatus::Pending,
            'activated_at' => null,
        ], $tenantAttributes));

        $owner = User::factory()->create([
            'role' => Role::TenantAdmin,
            'status' => UserStatus::Active,
            'tenant_id' => $tenant->getKey(),
        ]);

        $tenant->forceFill(['owner_user_id' => $owner->getKey()])->save();

        TenantMember::create([
            'tenant_id' => $tenant->getKey(),
            'user_id' => $owner->getKey(),
            'role' => 'owner',
            'status' => 'active',
            'joined_at' => now(),
        ]);

        $plan = SubscriptionPlan::factory()->create(array_merge([
            'price' => 99000,
            'duration_days' => 30,
        ], $planAttributes));

        foreach (range(0, 6) as $day) {
            $tenant->businessHours()->create([
                'day_of_week' => $day,
                'is_open' => true,
                'open_time' => '00:00',
                'close_time' => '23:59',
            ]);
        }

        $settings = TenantSettings::for($tenant);
        foreach (TenantSettings::defaults() as $key => $value) {
            $settings->set($key, $value);
        }

        $subscription = Subscription::create([
            'tenant_id' => $tenant->getKey(),
            'subscription_plan_id' => $plan->getKey(),
            'status' => SubscriptionStatus::Pending,
            'price' => $plan->price,
            'currency' => $plan->currency,
            'duration_days' => 30,
        ]);

        return compact('tenant', 'owner', 'plan', 'subscription');
    }
}
