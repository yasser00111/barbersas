<?php

namespace App\Listeners;

use App\Events\PaymentFailed;
use App\Notifications\PaymentFailedNotification;
use App\Services\Notifier;

class SendPaymentFailedNotification
{
    public function __construct(protected Notifier $notifier) {}

    public function handle(PaymentFailed $event): void
    {
        $payment = $event->payment;
        $user = $payment->invoice?->tenant?->owner;

        if (! $user) {
            return;
        }

        $this->notifier->send(
            $user,
            new PaymentFailedNotification($payment, $user->name),
            'payment_failed',
            'payment-failed:'.$payment->uuid,
            $payment->tenant_id,
        );
    }
}
