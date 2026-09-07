<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

final class EmployeePolicy
{
    public function view(User $user, Employee $employee): bool
    {
        return $user->isAdmin() || $employee->user_id === $user->id;
    }

    public function update(User $user, Employee $employee): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Employee $employee): bool
    {
        return $user->isAdmin();
    }

    public function sync_credentials(User $user, Employee $employee): bool
    {
        return $user->isAdmin();
    }
}