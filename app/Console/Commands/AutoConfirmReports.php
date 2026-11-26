<?php

namespace App\Console\Commands;

use App\Models\Report;
use App\Services\ActivityLogService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AutoConfirmReports extends Command
{
    protected $signature = 'reports:auto-confirm';

    protected $description = 'Konfirmasi otomatis laporan selesai yang tidak direspons selama tujuh hari.';

    public function handle(ActivityLogService $activityLogs): int
    {
        $confirmedAt = now();
        $cutoff = $confirmedAt->copy()->subDays(7);
        $count = 0;
        Report::query()
            ->where('status', Report::STATUS_SELESAI)
            ->whereNull('confirmed_at')
            ->whereNotNull('completed_at')
            ->where('completed_at', '<', $cutoff)
            ->chunkById(100, function ($reports) use ($confirmedAt, $cutoff, $activityLogs, &$count): void {
                foreach ($reports as $report) {
                    DB::transaction(function () use ($report, $confirmedAt, $cutoff, $activityLogs, &$count): void {
                        $lockedReport = Report::query()->lockForUpdate()->find($report->getKey());

                        if ($lockedReport === null
                            || $lockedReport->status !== Report::STATUS_SELESAI
                            || $lockedReport->confirmed_at !== null
                            || $lockedReport->completed_at === null
                            || $lockedReport->completed_at->gte($cutoff)) {
                            return;
                        }

                        $lockedReport->forceFill([
                            'confirmed_at' => $confirmedAt,
                            'updated_at' => $confirmedAt,
                        ])->save();
                        $activityLogs->record(
                            null,
                            'report_auto_confirmed',
                            $lockedReport,
                            ['confirmed_at' => null],
                            ['confirmed_at' => $confirmedAt->toIso8601String()],
                            $lockedReport->reporter_token !== null,
                        );
                        $count++;
                    });
                }
            });

        $this->info("{$count} laporan dikonfirmasi otomatis.");

        return self::SUCCESS;
    }
}
