<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    public function view(User $user, Payment $payment): bool
    {
        return $user->isTenantAdmin() && $user->belongsToTenant((int) $payment->tenant_id);
    }

    public function verify(User $user, Payment $payment): bool
    {
        return $user->isSuperAdmin();
    }
}
