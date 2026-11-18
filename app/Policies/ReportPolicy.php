<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Report;
use App\Models\ReportType;
use App\Models\User;

class ReportPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Report $report): bool
    {
        return match ($user->role) {
            UserRole::SUPER_ADMIN => true,
            UserRole::ADMIN_SARPRAS => $report->assigned_unit === ReportType::UNIT_SARPRAS,
            UserRole::ADMIN_KEMAHASISWAAN => $report->assigned_unit === ReportType::UNIT_KEMAHASISWAAN,
            UserRole::MAHASISWA => $this->isOwnedBy($user, $report) || $this->isPublic($report),
        };
    }

    public function update(User $user, Report $report): bool
    {
        return match ($user->role) {
            UserRole::SUPER_ADMIN => false,
            UserRole::ADMIN_SARPRAS => $report->assigned_unit === ReportType::UNIT_SARPRAS,
            UserRole::ADMIN_KEMAHASISWAAN => $report->assigned_unit === ReportType::UNIT_KEMAHASISWAAN,
            UserRole::MAHASISWA => false,
        };
    }

    public function manageVisibility(User $user, Report $report): bool
    {
        if ($report->requested_visibility !== Report::VISIBILITY_UMUM
            || $report->visibility_status !== Report::VISIBILITY_PENDING) {
            return false;
        }

        $typeCode = $report->reportType()->value('code');

        if (! in_array($typeCode, [ReportType::FACILITAS, ReportType::ASPIRASI], true)) {
            return false;
        }

        return match ($user->role) {
            UserRole::ADMIN_SARPRAS => $report->assigned_unit === ReportType::UNIT_SARPRAS && $typeCode === ReportType::FACILITAS,
            UserRole::ADMIN_KEMAHASISWAAN => $report->assigned_unit === ReportType::UNIT_KEMAHASISWAAN && $typeCode === ReportType::ASPIRASI,
            default => false,
        };
    }

    public function confirm(User $user, Report $report): bool
    {
        return $user->role === UserRole::MAHASISWA
            && $report->archived_at === null
            && $report->status === Report::STATUS_SELESAI
            && $report->confirmed_at === null
            && $this->isOwnedBy($user, $report);
    }

    public function reopen(User $user, Report $report): bool
    {
        return $this->confirm($user, $report);
    }

    private function isOwnedBy(User $user, Report $report): bool
    {
        return Report::query()->ownedBy($user, includeArchived: true)->whereKey($report->getKey())->exists();
    }

    private function isPublic(Report $report): bool
    {
        return Report::query()->public()->whereKey($report->getKey())->exists();
    }
}
