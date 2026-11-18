<?php

namespace App\Services;

use App\Models\Report;
use App\Models\ReportStatusHistory;
use App\Models\ReportType;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class ReportSubmissionService
{
    public function __construct(private readonly ActivityLogService $activityLogs) {}

    /** @param array<string, mixed> $data */
    public function submit(User $reporter, string $typeCode, array $data): Report
    {
        $reportType = ReportType::query()->where('code', $typeCode)->firstOrFail();
        $isViolation = $reportType->code === ReportType::PELANGGARAN;
        $isAnonymous = (bool) ($data['is_anonymous'] ?? false);
        $anonymousViolation = $isViolation && $isAnonymous;
        $photos = $data['photos'] ?? [];
        $storedPaths = [];

        try {
            return DB::transaction(function () use (
                $reporter,
                $reportType,
                $isViolation,
                $isAnonymous,
                $anonymousViolation,
                $photos,
                $data,
                &$storedPaths,
            ): Report {
                $report = Report::query()->create([
                    'ticket_no' => $this->ticketNumber(),
                    'reporter_id' => $anonymousViolation ? null : $reporter->getKey(),
                    'reporter_token' => $anonymousViolation ? Report::reporterTokenFor($reporter) : null,
                    'report_type_id' => $reportType->getKey(),
                    'category_id' => $data['category_id'],
                    'location_id' => $data['location_id'] ?? null,
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'incident_date' => $isViolation ? $data['incident_date'] : null,
                    'related_party' => $isViolation ? ($data['related_party'] ?? null) : null,
                    'status' => Report::STATUS_DIKIRIM,
                    'requested_visibility' => $isViolation ? Report::VISIBILITY_RAHASIA : $data['requested_visibility'],
                    'visibility_status' => $isViolation ? Report::VISIBILITY_APPROVED : Report::VISIBILITY_PENDING,
                    'approved_visibility' => $isViolation ? Report::VISIBILITY_RAHASIA : null,
                    'is_anonymous' => $isAnonymous,
                    'assigned_unit' => $reportType->assignedUnit(),
                ]);

                ReportStatusHistory::query()->create([
                    'report_id' => $report->getKey(),
                    'from_status' => null,
                    'to_status' => Report::STATUS_DIKIRIM,
                    'changed_by' => $anonymousViolation ? null : $reporter->getKey(),
                    'created_at' => now(),
                ]);

                foreach ($photos as $photo) {
                    if (! $photo instanceof UploadedFile) {
                        continue;
                    }

                    $path = $photo->store('reports/'.now()->format('Y/m'), 'local');

                    if (! is_string($path)) {
                        throw new RuntimeException('Lampiran laporan gagal disimpan.');
                    }

                    $storedPaths[] = $path;

                    $report->attachments()->create([
                        'file_path' => $path,
                        'kind' => $isViolation ? 'evidence' : 'before',
                        'uploaded_by' => $anonymousViolation ? null : $reporter->getKey(),
                        'created_at' => now(),
                    ]);
                }

                $this->activityLogs->record(
                    $anonymousViolation ? null : $reporter,
                    'report_submitted',
                    $report,
                    [],
                    ['status' => Report::STATUS_DIKIRIM, 'report_type' => $reportType->code],
                    $anonymousViolation,
                );

                if (count($storedPaths) > 0) {
                    $this->activityLogs->record(
                        $anonymousViolation ? null : $reporter,
                        'attachment_uploaded',
                        $report,
                        [],
                        ['count' => count($storedPaths), 'kind' => $isViolation ? 'evidence' : 'before'],
                        $anonymousViolation,
                    );
                }

                return $report;
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($storedPaths);

            throw $exception;
        }
    }

    private function ticketNumber(): string
    {
        do {
            $ticketNumber = 'IDN-'.now()->format('ymd').'-'.Str::upper(Str::random(8));
        } while (Report::query()->where('ticket_no', $ticketNumber)->exists());

        return $ticketNumber;
    }
}
