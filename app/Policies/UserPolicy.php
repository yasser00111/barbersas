<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isTenantAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isTenantAdmin();
    }

    public function update(User $user, User $target): bool
    {
        if (! $user->isTenantAdmin()) {
            return false;
        }

        return $user->belongsToTenant((int) $target->tenant_id);
    }

    public function delete(User $user, User $target): bool
    {
        return $user->isTenantAdmin()
            && $user->belongsToTenant((int) $target->tenant_id)
            && $user->getKey() !== $target->getKey();
    }
}
