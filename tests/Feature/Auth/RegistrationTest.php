<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Enums\SubscriptionStatus;
use App\Enums\TenantStatus;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function payload(array $overrides = []): array
    {
        return array_merge([
            'owner_name' => 'Budi Owner',
            'email' => 'budi@example.com',
            'phone' => '081234567890',
            'business_name' => 'Barber Budi',
            'business_phone' => '081234567891',
            'city' => 'Bandung',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ], $overrides);
    }

    public function test_registration_creates_pending_tenant_with_owner_membership_and_subscription(): void
    {
        Notification::fake();

        $plan = SubscriptionPlan::factory()->create(['price' => 99000, 'duration_days' => 30]);

        $response = $this->post('/register', $this->payload(['plan_id' => $plan->id]));

        $response->assertRedirect(route('admin.subscription.index'));

        $user = User::where('email', 'budi@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame(Role::TenantAdmin, $user->role);

        $tenant = Tenant::where('slug', 'barber-budi')->first();
        $this->assertNotNull($tenant);
        $this->assertSame(TenantStatus::Pending, $tenant->status);
        $this->assertSame($user->tenant_id, $tenant->id);
        $this->assertSame($user->id, $tenant->owner_user_id);

        $this->assertDatabaseHas('tenant_members', [
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'status' => 'active',
        ]);

        $subscription = $tenant->subscriptions()->first();
        $this->assertNotNull($subscription);
        $this->assertSame(SubscriptionStatus::Pending, $subscription->status);
        $this->assertSame('99000.00', $subscription->price);

        $this->assertDatabaseHas('invoices', [
            'tenant_id' => $tenant->id,
            'subscription_id' => $subscription->id,
        ]);

        $this->assertDatabaseCount('business_hours', 7);
        $this->assertAuthenticatedAs($user);
    }

    public function test_registration_fails_for_invalid_plan(): void
    {
        Notification::fake();

        $response = $this->post('/register', $this->payload(['plan_id' => 9999]));

        $response->assertSessionHasErrors('plan_id');
        $this->assertGuest();
        $this->assertDatabaseCount('tenants', 0);
    }

    public function test_registration_requires_unique_email(): void
    {
        Notification::fake();

        User::factory()->create(['email' => 'budi@example.com']);
        $plan = SubscriptionPlan::factory()->create();

        $response = $this->post('/register', $this->payload(['plan_id' => $plan->id]));

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_verification_email_is_sent_on_registration(): void
    {
        Notification::fake();

        $plan = SubscriptionPlan::factory()->create();

        $this->post('/register', $this->payload(['plan_id' => $plan->id]));

        $user = User::where('email', 'budi@example.com')->firstOrFail();

        Notification::assertSentTo($user, VerifyEmail::class);
    }
}
