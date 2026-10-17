<?php

namespace App\Console\Commands;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Notifications\SubscriptionExpiringReminder;
use App\Services\Notifier;
use App\Services\SubscriptionService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Scheduled subscription maintenance: expires lapsed subscriptions and sends
 * deduplicated expiry reminders (7/3/1 days before).
 *
 * Access control never relies solely on this command: the live middleware
 * always re-checks the subscription's ends_at. The scheduler is only for
 * status bookkeeping and notifications.
 */
class CheckSubscriptions extends Command
{
    protected $signature = 'barbersaas:subscriptions:check';

    protected $description = 'Expire lapsed subscriptions and send expiry reminders';

    public function __construct(
        protected SubscriptionService $subscriptions,
        protected Notifier $notifier,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $graceDays = (int) config('barbersaas.subscription.grace_days', 0);
        $expireBefore = Carbon::now()->subDays($graceDays);

        $expired = 0;

        Subscription::query()
            ->where('status', SubscriptionStatus::Active->value)
            ->whereNotNull('ends_at')
            ->where('ends_at', '<', $expireBefore)
            ->orderBy('id')
            ->chunkById(100, function ($subscriptions) use (&$expired) {
                foreach ($subscriptions as $subscription) {
                    $this->subscriptions->expire($subscription);
                    $expired++;
                }
            });

        $reminders = $this->sendReminders();

        $this->info("Kedaluwarsa: {$expired} langganan. Pengingat terkirim: {$reminders}.");

        return self::SUCCESS;
    }

    protected function sendReminders(): int
    {
        $dayOptions = (array) config('barbersaas.subscription.reminder_days', [7, 3, 1]);
        $sent = 0;

        Subscription::query()
            ->with(['plan', 'tenant.owner'])
            ->where('status', SubscriptionStatus::Active->value)
            ->whereNotNull('ends_at')
            ->whereBetween('ends_at', [now(), now()->copy()->addDays(max($dayOptions))])
            ->orderBy('id')
            ->chunkById(100, function ($subscriptions) use ($dayOptions, &$sent) {
                foreach ($subscriptions as $subscription) {
                    $daysLeft = (int) now()->startOfDay()->diffInDays($subscription->ends_at->startOfDay(), false);

                    if (! in_array($daysLeft, $dayOptions, true)) {
                        continue;
                    }

                    $user = $subscription->tenant?->owner;

                    if (! $user) {
                        continue;
                    }

                    $key = 'subscription-reminder:'.$subscription->getKey().':'.$daysLeft.':'.$subscription->ends_at->toDateString();

                    if ($this->notifier->send(
                        $user,
                        new SubscriptionExpiringReminder($subscription, $daysLeft),
                        'subscription_reminder',
                        $key,
                        $subscription->tenant_id,
                    )) {
                        $sent++;
                    }
                }
            });

        return $sent;
    }
}
