<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\ReportType;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [
            UserRole::ADMIN_SARPRAS,
            UserRole::ADMIN_KEMAHASISWAAN,
            UserRole::SUPER_ADMIN,
        ], true);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function manageType(User $user, ReportType $type): bool
    {
        return match ($user->role) {
            UserRole::SUPER_ADMIN => true,
            UserRole::ADMIN_SARPRAS => $type->code === ReportType::FACILITAS,
            UserRole::ADMIN_KEMAHASISWAAN => in_array($type->code, [
                ReportType::ASPIRASI,
                ReportType::PELANGGARAN,
            ], true),
            UserRole::MAHASISWA => false,
        };
    }

    public function view(User $user, Category $category): bool
    {
        return $this->manageType($user, $category->reportType);
    }

    public function update(User $user, Category $category): bool
    {
        return $this->manageType($user, $category->reportType);
    }
}
