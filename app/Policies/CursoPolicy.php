<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Academia\Curso;
use App\Models\User;

final class CursoPolicy
{
    public function view(User $user, Curso $curso): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Curso $curso): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Curso $curso): bool
    {
        return $user->isAdmin();
    }
}