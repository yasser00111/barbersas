<?php

namespace App\Policies;

use App\Models\SubscriptionPlan;
use App\Models\User;

class SubscriptionPlanPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, SubscriptionPlan $plan): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, SubscriptionPlan $plan): bool
    {
        return false;
    }

    public function delete(User $user, SubscriptionPlan $plan): bool
    {
        return false;
    }
}
