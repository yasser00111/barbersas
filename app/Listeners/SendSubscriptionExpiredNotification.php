<?php

namespace App\Listeners;

use App\Events\SubscriptionExpired;
use App\Notifications\SubscriptionExpiredNotification;
use App\Services\Notifier;

class SendSubscriptionExpiredNotification
{
    public function __construct(protected Notifier $notifier) {}

    public function handle(SubscriptionExpired $event): void
    {
        $subscription = $event->subscription;
        $user = $subscription->tenant?->owner;

        if (! $user) {
            return;
        }

        $this->notifier->send(
            $user,
            new SubscriptionExpiredNotification($subscription, $user->name),
            'subscription_expired',
            'subscription-expired:'.$subscription->getKey().':'.($subscription->ends_at?->timestamp ?? 0),
            $subscription->tenant_id,
        );
    }
}
