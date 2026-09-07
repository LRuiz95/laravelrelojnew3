<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Academia\Alumno;
use App\Models\User;

final class AlumnoPolicy
{
    public function view(User $user, Alumno $alumno): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Alumno $alumno): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Alumno $alumno): bool
    {
        return $user->isAdmin();
    }
}