<?php

namespace App\Services;

use App\Models\Report;
use App\Models\ReportStatusHistory;
use App\Models\ReportType;
use App\Models\User;
use App\Notifications\ReportStatusNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class ReportStatusService
{
    public function __construct(private readonly ActivityLogService $activityLogs) {}

    /** @param array{status: string, note?: string|null, after_photo?: UploadedFile|null} $data */
    public function update(Report $report, User $actor, array $data): Report
    {
        $storedPath = null;

        try {
            return DB::transaction(function () use ($report, $actor, $data, &$storedPath): Report {
                $lockedReport = Report::query()
                    ->with('reportType')
                    ->lockForUpdate()
                    ->findOrFail($report->getKey());
                $nextStatus = $data['status'];
                $allowedStatuses = match ($lockedReport->status) {
                    Report::STATUS_DIKIRIM => [Report::STATUS_DIVERIFIKASI, Report::STATUS_DITOLAK],
                    Report::STATUS_DIVERIFIKASI => [Report::STATUS_DIPROSES, Report::STATUS_DITOLAK],
                    Report::STATUS_DIPROSES => [Report::STATUS_SELESAI, Report::STATUS_DITOLAK],
                    default => [],
                };

                if (! in_array($nextStatus, $allowedStatuses, true)) {
                    throw ValidationException::withMessages([
                        'status' => 'Status laporan sudah berubah atau transisi tidak diperbolehkan. Muat ulang halaman.',
                    ]);
                }

                $photo = $data['after_photo'] ?? null;
                $isFacility = $lockedReport->reportType->code === ReportType::FACILITAS;
                $hasAfterPhoto = $isFacility && $lockedReport->attachments()->where('kind', 'after')->exists();

                if ($isFacility && $nextStatus === Report::STATUS_SELESAI && ! $hasAfterPhoto && ! $photo instanceof UploadedFile) {
                    throw ValidationException::withMessages([
                        'afterPhoto' => 'Unggah minimal satu foto sesudah sebelum laporan fasilitas diselesaikan.',
                    ]);
                }

                if ($photo instanceof UploadedFile) {
                    $storedPath = $photo->store('reports/'.now()->format('Y/m'), 'local');

                    if (! is_string($storedPath)) {
                        throw new \RuntimeException('Foto sesudah gagal disimpan.');
                    }

                    $lockedReport->attachments()->create([
                        'file_path' => $storedPath,
                        'kind' => 'after',
                        'uploaded_by' => $actor->getKey(),
                        'created_at' => now(),
                    ]);
                }

                $previousStatus = $lockedReport->status;
                $lockedReport->status = $nextStatus;

                if ($nextStatus === Report::STATUS_SELESAI) {
                    $lockedReport->completed_at = now();
                    $lockedReport->confirmed_at = null;
                }

                if ($nextStatus === Report::STATUS_DITOLAK) {
                    $lockedReport->rejected_reason = $data['note'];
                }

                $lockedReport->save();

                ReportStatusHistory::query()->create([
                    'report_id' => $lockedReport->getKey(),
                    'from_status' => $previousStatus,
                    'to_status' => $nextStatus,
                    'note' => $data['note'] ?? null,
                    'changed_by' => $actor->getKey(),
                    'created_at' => now(),
                ]);

                $this->activityLogs->record(
                    $actor,
                    'report_status_updated',
                    $lockedReport,
                    ['status' => $previousStatus],
                    ['status' => $nextStatus],
                );

                if ($photo instanceof UploadedFile) {
                    $this->activityLogs->record($actor, 'attachment_uploaded', $lockedReport, [], ['kind' => 'after']);
                }

                // Anonymous violations have no reporter_id and must never gain a notification identity link.
                if ($lockedReport->reporter_id !== null) {
                    $lockedReport->reporter?->notify(new ReportStatusNotification($lockedReport));
                }

                return $lockedReport->fresh(['reportType', 'attachments', 'statusHistories']);
            });
        } catch (Throwable $exception) {
            if (is_string($storedPath)) {
                Storage::disk('local')->delete($storedPath);
            }

            throw $exception;
        }
    }
}
