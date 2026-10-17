<?php

namespace App\Listeners;

use App\Events\SubscriptionActivated;
use App\Notifications\SubscriptionActivatedNotification;
use App\Services\Notifier;

class SendSubscriptionActivatedNotification
{
    public function __construct(protected Notifier $notifier) {}

    public function handle(SubscriptionActivated $event): void
    {
        $subscription = $event->subscription;
        $user = $subscription->tenant?->owner;

        if (! $user) {
            return;
        }

        $this->notifier->send(
            $user,
            new SubscriptionActivatedNotification($subscription, $user->name),
            'subscription_activated',
            'subscription-activated:'.$subscription->getKey().':'.($subscription->ends_at?->timestamp ?? 0),
            $subscription->tenant_id,
        );
    }
}
