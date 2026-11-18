<?php

namespace App\Services;

use App\Models\Report;
use App\Models\ReportStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReportLifecycleService
{
    public function __construct(private readonly ActivityLogService $activityLogs) {}

    public function confirm(Report $report, User $reporter): Report
    {
        return DB::transaction(function () use ($report, $reporter): Report {
            $lockedReport = Report::query()->lockForUpdate()->findOrFail($report->getKey());

            if (! $this->isOwnedBy($lockedReport, $reporter)
                || $lockedReport->status !== Report::STATUS_SELESAI
                || $lockedReport->confirmed_at !== null) {
                throw ValidationException::withMessages([
                    'report' => 'Laporan ini tidak sedang menunggu konfirmasi.',
                ]);
            }

            $lockedReport->forceFill(['confirmed_at' => now()])->save();
            $this->activityLogs->record(
                $reporter,
                'report_confirmed',
                $lockedReport,
                ['confirmed_at' => null],
                ['confirmed_at' => $lockedReport->confirmed_at->toIso8601String()],
                $lockedReport->reporter_token !== null,
            );

            return $lockedReport->fresh();
        });
    }

    public function reopen(Report $report, User $reporter, string $reason): Report
    {
        return DB::transaction(function () use ($report, $reporter, $reason): Report {
            $lockedReport = Report::query()->lockForUpdate()->findOrFail($report->getKey());

            if (! $this->isOwnedBy($lockedReport, $reporter)
                || $lockedReport->status !== Report::STATUS_SELESAI
                || $lockedReport->confirmed_at !== null) {
                throw ValidationException::withMessages([
                    'report' => 'Laporan ini tidak dapat dibuka kembali.',
                ]);
            }

            $previousStatus = $lockedReport->status;
            $lockedReport->forceFill([
                'status' => Report::STATUS_DIPROSES,
                'reopened_count' => $lockedReport->reopened_count + 1,
                'completed_at' => null,
                'confirmed_at' => null,
            ])->save();

            ReportStatusHistory::query()->create([
                'report_id' => $lockedReport->getKey(),
                'from_status' => $previousStatus,
                'to_status' => Report::STATUS_DIPROSES,
                'note' => $reason,
                // A token-owned violation must not link the reporter to this action.
                'changed_by' => $lockedReport->reporter_token === null ? $reporter->getKey() : null,
                'created_at' => now(),
            ]);

            $this->activityLogs->record(
                $reporter,
                'report_reopened',
                $lockedReport,
                ['status' => $previousStatus, 'reopened_count' => $lockedReport->reopened_count - 1],
                ['status' => $lockedReport->status, 'reopened_count' => $lockedReport->reopened_count],
                $lockedReport->reporter_token !== null,
            );

            return $lockedReport->fresh(['reportType', 'attachments', 'statusHistories']);
        });
    }

    private function isOwnedBy(Report $report, User $reporter): bool
    {
        return Report::query()->ownedBy($reporter)->whereKey($report->getKey())->exists();
    }
}
