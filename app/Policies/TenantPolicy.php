<?php

namespace App\Policies;

use App\Models\Tenant;
use App\Models\User;

class TenantPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, Tenant $tenant): bool
    {
        return $user->belongsToTenant($tenant->getKey());
    }

    public function update(User $user, Tenant $tenant): bool
    {
        return $user->isTenantAdmin() && $user->belongsToTenant($tenant->getKey());
    }

    public function manageSubscription(User $user, Tenant $tenant): bool
    {
        return $user->isTenantAdmin() && $user->belongsToTenant($tenant->getKey());
    }

    public function manageStaff(User $user, Tenant $tenant): bool
    {
        return $user->isTenantAdmin() && $user->belongsToTenant($tenant->getKey());
    }
}
