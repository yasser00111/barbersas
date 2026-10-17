<?php

namespace App\Services;

use App\Models\EmailLog;
use App\Models\User;
use Illuminate\Notifications\Notification;

/**
 * Sends notifications while guaranteeing that the same notification is never
 * delivered twice for the same logical event (durable dedupe key backed by a
 * unique database constraint on email_logs.dedupe_key).
 */
class Notifier
{
    public function send(
        User $user,
        Notification $notification,
        string $type,
        string $dedupeKey,
        ?int $tenantId = null,
    ): bool {
        if (EmailLog::query()->where('dedupe_key', $dedupeKey)->exists()) {
            return false;
        }

        EmailLog::create([
            'tenant_id' => $tenantId ?? $user->tenant_id,
            'recipient' => $user->email,
            'type' => $type,
            'dedupe_key' => $dedupeKey,
            'sent_at' => now(),
        ]);

        $user->notify($notification);

        return true;
    }

    public function sent(string $dedupeKey): bool
    {
        return EmailLog::query()->where('dedupe_key', $dedupeKey)->exists();
    }
}
