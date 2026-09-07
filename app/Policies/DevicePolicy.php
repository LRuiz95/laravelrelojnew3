<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Device;
use App\Models\User;

final class DevicePolicy
{
    public function view(User $user, Device $device): bool
    {
        return $user->isAdmin() || $device->id === $user->device_id;
    }

    public function update(User $user, Device $device): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Device $device): bool
    {
        return $user->isAdmin();
    }

    public function sync_credentials(User $user, Device $device): bool
    {
        return $user->isAdmin();
    }
}