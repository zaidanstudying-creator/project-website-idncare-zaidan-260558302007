<?php

namespace App\Services;

use App\Models\Report;
use App\Models\ReportType;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ReportVisibilityService
{
    public function __construct(private readonly ActivityLogService $activityLogs) {}

    public function decide(Report $report, User $actor, string $decision): Report
    {
        return DB::transaction(function () use ($report, $actor, $decision): Report {
            $lockedReport = Report::query()->with('reportType')->lockForUpdate()->findOrFail($report->getKey());
            Gate::forUser($actor)->authorize('manageVisibility', $lockedReport);

            if (! in_array($lockedReport->reportType->code, [ReportType::FACILITAS, ReportType::ASPIRASI], true)
                || $lockedReport->requested_visibility !== Report::VISIBILITY_UMUM
                || $lockedReport->visibility_status !== Report::VISIBILITY_PENDING) {
                throw ValidationException::withMessages([
                    'report' => 'Permintaan visibilitas ini sudah diproses atau tidak dapat dipublikasikan.',
                ]);
            }

            $approved = $decision === 'approve';
            $previousStatus = $lockedReport->visibility_status;
            $previousVisibility = $lockedReport->approved_visibility;
            $lockedReport->forceFill([
                'visibility_status' => $approved ? Report::VISIBILITY_APPROVED : Report::VISIBILITY_REJECTED,
                'approved_visibility' => $approved ? Report::VISIBILITY_UMUM : Report::VISIBILITY_RAHASIA,
            ])->save();

            $this->activityLogs->record($actor, 'report_visibility_decided', $lockedReport,
                ['visibility_status' => $previousStatus, 'approved_visibility' => $previousVisibility],
                ['visibility_status' => $lockedReport->visibility_status, 'approved_visibility' => $lockedReport->approved_visibility]);

            return $lockedReport->fresh(['reportType']);
        });
    }
}
