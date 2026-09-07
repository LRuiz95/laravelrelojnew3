<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Academia\Profesor;
use App\Models\User;

final class ProfesorPolicy
{
    public function view(User $user, Profesor $profesor): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Profesor $profesor): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Profesor $profesor): bool
    {
        return $user->isAdmin();
    }
}