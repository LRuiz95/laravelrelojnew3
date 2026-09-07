<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Academia\Ciclo;
use App\Models\User;

final class CicloPolicy
{
    public function view(User $user, Ciclo $ciclo): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Ciclo $ciclo): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Ciclo $ciclo): bool
    {
        return $user->isAdmin();
    }
}