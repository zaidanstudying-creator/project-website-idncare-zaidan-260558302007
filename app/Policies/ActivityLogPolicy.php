<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\User;

class ActivityLogPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [
            UserRole::ADMIN_SARPRAS,
            UserRole::ADMIN_KEMAHASISWAAN,
            UserRole::SUPER_ADMIN,
        ], true);
    }

    public function view(User $user, ActivityLog $activityLog): bool
    {
        return $this->viewAny($user);
    }
}
