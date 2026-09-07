<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Academia\HorarioDet;
use App\Models\User;

final class HorarioPolicy
{
    public function view(User $user, HorarioDet $horario): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, HorarioDet $horario): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, HorarioDet $horario): bool
    {
        return $user->isAdmin();
    }
}