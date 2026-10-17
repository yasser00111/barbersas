<?php

namespace App\Listeners;

use App\Events\PaymentVerified;
use App\Notifications\PaymentVerifiedNotification;
use App\Services\Notifier;

class SendPaymentVerifiedNotification
{
    public function __construct(protected Notifier $notifier) {}

    public function handle(PaymentVerified $event): void
    {
        $payment = $event->payment;
        $user = $payment->invoice?->tenant?->owner;

        if (! $user) {
            return;
        }

        $this->notifier->send(
            $user,
            new PaymentVerifiedNotification($payment, $user->name),
            'payment_verified',
            'payment-verified:'.$payment->uuid,
            $payment->tenant_id,
        );
    }
}
