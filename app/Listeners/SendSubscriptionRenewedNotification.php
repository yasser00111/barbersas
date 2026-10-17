<?php

namespace App\Listeners;

use App\Events\SubscriptionRenewed;
use App\Notifications\SubscriptionRenewedNotification;
use App\Services\Notifier;

class SendSubscriptionRenewedNotification
{
    public function __construct(protected Notifier $notifier) {}

    public function handle(SubscriptionRenewed $event): void
    {
        $subscription = $event->subscription;
        $user = $subscription->tenant?->owner;

        if (! $user) {
            return;
        }

        $this->notifier->send(
            $user,
            new SubscriptionRenewedNotification($subscription, $user->name),
            'subscription_renewed',
            'subscription-renewed:'.$subscription->getKey().':'.($subscription->ends_at?->timestamp ?? 0),
            $subscription->tenant_id,
        );
    }
}
