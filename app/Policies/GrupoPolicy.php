<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Academia\Grupo;
use App\Models\User;

final class GrupoPolicy
{
    public function view(User $user, Grupo $grupo): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Grupo $grupo): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Grupo $grupo): bool
    {
        return $user->isAdmin();
    }
}