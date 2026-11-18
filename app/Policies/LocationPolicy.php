<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Location;
use App\Models\User;

class LocationPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->canManage($user);
    }

    public function create(User $user): bool
    {
        return $this->canManage($user);
    }

    public function update(User $user, Location $location): bool
    {
        return $this->canManage($user);
    }

    private function canManage(User $user): bool
    {
        return in_array($user->role, [
            UserRole::ADMIN_SARPRAS,
            UserRole::ADMIN_KEMAHASISWAAN,
            UserRole::SUPER_ADMIN,
        ], true);
    }
}
