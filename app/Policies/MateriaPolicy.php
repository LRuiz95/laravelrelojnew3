<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Academia\Materia;
use App\Models\User;

final class MateriaPolicy
{
    public function view(User $user, Materia $materia): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Materia $materia): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Materia $materia): bool
    {
        return $user->isAdmin();
    }
}