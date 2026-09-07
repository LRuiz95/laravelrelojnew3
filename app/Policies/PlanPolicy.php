<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Academia\Plan;
use App\Models\User;

final class PlanPolicy
{
    public function view(User $user, Plan $plan): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Plan $plan): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Plan $plan): bool
    {
        return $user->isAdmin();
    }
}