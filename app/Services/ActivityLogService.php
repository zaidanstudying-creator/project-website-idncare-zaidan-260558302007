<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ActivityLogService
{
    /** @param array<string, mixed> $oldValues @param array<string, mixed> $newValues */
    public function record(
        ?User $actor,
        string $action,
        ?Model $subject = null,
        array $oldValues = [],
        array $newValues = [],
        bool $anonymous = false,
    ): ActivityLog {
        return ActivityLog::query()->create([
            'user_id' => $anonymous ? null : $actor?->getKey(),
            'role' => $anonymous ? 'mahasiswa' : $actor?->role?->value,
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'old_values' => $oldValues ?: null,
            'new_values' => $newValues ?: null,
            'ip_address' => $anonymous ? null : request()->ip(),
            'created_at' => now(),
        ]);
    }
}
