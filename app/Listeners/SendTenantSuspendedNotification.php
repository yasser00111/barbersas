<?php

namespace App\Listeners;

use App\Events\TenantSuspended;
use App\Notifications\TenantSuspendedNotification;
use App\Services\Notifier;

class SendTenantSuspendedNotification
{
    public function __construct(protected Notifier $notifier) {}

    public function handle(TenantSuspended $event): void
    {
        $tenant = $event->tenant;
        $user = $tenant->owner;

        if (! $user) {
            return;
        }

        $this->notifier->send(
            $user,
            new TenantSuspendedNotification($user->name),
            'tenant_suspended',
            'tenant-suspended:'.$tenant->getKey().':'.($tenant->suspended_at?->timestamp ?? now()->timestamp),
            $tenant->getKey(),
        );
    }
}
