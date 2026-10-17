<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    public function view(User $user, Invoice $invoice): bool
    {
        return $user->isTenantAdmin() && $user->belongsToTenant((int) $invoice->tenant_id);
    }

    public function uploadPayment(User $user, Invoice $invoice): bool
    {
        return $user->isTenantAdmin() && $user->belongsToTenant((int) $invoice->tenant_id);
    }

    public function pay(User $user, Invoice $invoice): bool
    {
        return $this->uploadPayment($user, $invoice) && $invoice->isPayable();
    }
}
