<?php

namespace Tests\Feature\Billing;

use App\Enums\SubscriptionStatus;
use App\Models\EmailLog;
use App\Notifications\SubscriptionExpiringReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SubscriptionMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    public function test_command_expires_lapsed_subscriptions(): void
    {
        ['tenant' => $tenant, 'subscription' => $subscription] = $this->activeTenant();

        $subscription->forceFill(['ends_at' => now()->subDay()])->save();

        $this->artisan('barbersaas:subscriptions:check')->assertSuccessful();

        $this->assertSame(SubscriptionStatus::Expired, $subscription->fresh()->status);
    }

    public function test_command_sends_expiry_reminder_once(): void
    {
        ['tenant' => $tenant, 'owner' => $owner, 'subscription' => $subscription] = $this->activeTenant();

        $subscription->forceFill(['ends_at' => now()->addDays(3)])->save();

        $this->artisan('barbersaas:subscriptions:check')->assertSuccessful();

        Notification::assertSentToTimes($owner, SubscriptionExpiringReminder::class, 1);
        $this->assertSame(1, EmailLog::where('type', 'subscription_reminder')->count());

        $this->artisan('barbersaas:subscriptions:check')->assertSuccessful();

        Notification::assertSentToTimes($owner, SubscriptionExpiringReminder::class, 1);
    }

    public function test_command_ignores_subscriptions_far_from_expiry(): void
    {
        ['tenant' => $tenant, 'owner' => $owner, 'subscription' => $subscription] = $this->activeTenant();

        $subscription->forceFill(['ends_at' => now()->addDays(20)])->save();

        $this->artisan('barbersaas:subscriptions:check')->assertSuccessful();

        Notification::assertNothingSent();
        $this->assertSame(SubscriptionStatus::Active, $subscription->fresh()->status);
    }
}
