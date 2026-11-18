<?php

namespace App\Livewire\Pages\Dashboard;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\Report;
use App\Models\ReportStatusHistory;
use App\Models\ReportType;
use App\Models\User;
use App\Services\ReportLifecycleService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['showPageHeading' => false])]
class Index extends Component
{
    public function confirmReport(int $reportId, ReportLifecycleService $lifecycle): void
    {
        $report = Report::query()->ownedBy(auth()->user())->findOrFail($reportId);
        Gate::authorize('confirm', $report);
        $lifecycle->confirm($report, auth()->user());
        session()->flash('status', 'Hasil laporan berhasil dikonfirmasi.');
    }

    public function render(): View
    {
        $user = auth()->user();
        $allReports = Report::query();
        $reports = match ($user->role) {
            UserRole::MAHASISWA => (clone $allReports)->ownedBy($user),
            UserRole::ADMIN_SARPRAS, UserRole::ADMIN_KEMAHASISWAAN => (clone $allReports)->visibleTo($user),
            UserRole::SUPER_ADMIN => clone $allReports,
        };

        $statusCounts = collect([
            Report::STATUS_DIKIRIM,
            Report::STATUS_DIVERIFIKASI,
            Report::STATUS_DIPROSES,
            Report::STATUS_SELESAI,
            Report::STATUS_DITOLAK,
        ])->mapWithKeys(fn (string $status) => [$status => (clone $reports)->where('status', $status)->count()]);
        $total = (clone $reports)->count();
        $recentReports = (clone $reports)->with(['reportType', 'category', 'location'])
            ->orderByDesc('created_at')->orderByDesc('id')->limit(5)->get();
        $pendingConfirmation = $user->role === UserRole::MAHASISWA
            ? (clone $reports)->with(['reportType'])->where('status', Report::STATUS_SELESAI)
                ->whereNull('confirmed_at')->latest('updated_at')->first()
            : null;
        $months = collect(range(5, 0))->map(fn (int $offset) => now()->startOfMonth()->subMonths($offset));
        $monthly = $months->map(fn ($month) => [
            'label' => $month->translatedFormat('M'),
            'count' => (clone $reports)->whereBetween('created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])->count(),
        ]);
        $byCategory = (clone $reports)->selectRaw('category_id, COUNT(*) as total')
            ->whereNotNull('category_id')->groupBy('category_id')->with('category:id,name')
            ->orderByDesc('total')->limit(6)->get()
            ->map(fn (Report $report) => ['label' => $report->category?->name ?? 'Kategori lain', 'count' => (int) $report->total]);
        $byLocation = (clone $reports)->selectRaw('location_id, COUNT(*) as total')
            ->whereNotNull('location_id')->groupBy('location_id')->with('location:id,name')
            ->orderByDesc('total')->limit(5)->get()
            ->map(fn (Report $report) => ['label' => $report->location?->name ?? 'Lokasi lain', 'count' => (int) $report->total]);
        $byType = (clone $reports)->selectRaw('report_type_id, COUNT(*) as total')
            ->groupBy('report_type_id')->with('reportType:id,code,name')->orderByDesc('total')->get()
            ->map(fn (Report $report) => ['label' => $report->reportType?->name ?? 'Jenis lain', 'count' => (int) $report->total]);
        $pendingVisibility = (clone $reports)->where('requested_visibility', Report::VISIBILITY_UMUM)
            ->where('visibility_status', Report::VISIBILITY_PENDING)->count();
        $completedReports = (clone $reports)->whereNotNull('completed_at')->get(['assigned_unit', 'created_at', 'completed_at']);
        $resolutionDaysByUnit = collect([ReportType::UNIT_SARPRAS, ReportType::UNIT_KEMAHASISWAAN])->mapWithKeys(function (string $unit) use ($completedReports): array {
            $unitReports = $completedReports->where('assigned_unit', $unit);

            return [$unit => $unitReports->isEmpty()
                ? null
                : round($unitReports->avg(fn (Report $report) => $report->created_at->diffInSeconds($report->completed_at)) / 86400, 1)];
        });
        $firstResponseTimes = ReportStatusHistory::query()
            ->selectRaw('report_id, MIN(created_at) as first_response_at')
            ->whereIn('to_status', [Report::STATUS_DIVERIFIKASI, Report::STATUS_DITOLAK])
            ->whereIn('report_id', (clone $reports)->select('reports.id'))
            ->groupBy('report_id')->get()->keyBy('report_id');
        $respondedReports = $firstResponseTimes->isEmpty()
            ? collect()
            : (clone $reports)->whereIn('id', $firstResponseTimes->keys())->get(['id', 'assigned_unit', 'created_at']);
        $overallResponseDays = $respondedReports->isEmpty()
            ? null
            : round($respondedReports->avg(fn (Report $report) => $report->created_at
                ->diffInSeconds(Carbon::parse($firstResponseTimes[$report->id]->first_response_at))) / 86400, 1);
        $overallResolutionDays = $completedReports->isEmpty()
            ? null
            : round($completedReports->avg(fn (Report $report) => $report->created_at->diffInSeconds($report->completed_at)) / 86400, 1);
        $responseDaysByUnit = collect([ReportType::UNIT_SARPRAS, ReportType::UNIT_KEMAHASISWAAN])->mapWithKeys(function (string $unit) use ($respondedReports, $firstResponseTimes): array {
            $unitReports = $respondedReports->where('assigned_unit', $unit);

            return [$unit => $unitReports->isEmpty()
                ? null
                : round($unitReports->avg(fn (Report $report) => $report->created_at
                    ->diffInSeconds(Carbon::parse($firstResponseTimes[$report->id]->first_response_at))) / 86400, 1)];
        });

        $analytics = [];
        if ($user->role !== UserRole::MAHASISWA) {
            $analytics['role'] = $user->role->value;
            $reportTypes = ReportType::query()->orderBy('id')->get(['id', 'code', 'name']);
            $trendTypes = $reportTypes->filter(fn (ReportType $type) => $user->role === UserRole::SUPER_ADMIN
                || ($user->role === UserRole::ADMIN_SARPRAS && $type->code === ReportType::FACILITAS)
                || ($user->role === UserRole::ADMIN_KEMAHASISWAAN && in_array($type->code, [ReportType::ASPIRASI, ReportType::PELANGGARAN], true)));
            $analyticsMonths = $months->values();
            $analytics['monthlyByType'] = [
                'labels' => $analyticsMonths->map(fn ($month) => $month->translatedFormat('M'))->all(),
                'datasets' => $user->role === UserRole::ADMIN_SARPRAS
                    ? [
                        ['label' => 'Laporan masuk', 'data' => $monthly->pluck('count')->all()],
                        ['label' => 'Selesai', 'data' => $analyticsMonths->map(fn ($month) => (clone $reports)->whereNotNull('completed_at')
                            ->whereBetween('completed_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])->count())->all()],
                    ]
                    : $trendTypes->map(function (ReportType $type) use ($reports, $analyticsMonths): array {
                        return [
                            'label' => $type->name,
                            'data' => $analyticsMonths->map(fn ($month) => (clone $reports)
                                ->where('report_type_id', $type->id)
                                ->whereBetween('created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
                                ->count())->all(),
                        ];
                    })->values()->all(),
            ];
            $analytics['status'] = ['labels' => $statusCounts->keys()->map(fn ($status) => ucfirst($status))->all(), 'values' => $statusCounts->values()->all()];
            $analytics['byLocation'] = $byLocation->all();
            $analytics['byCategory'] = $byCategory->all();
            $analytics['byType'] = collect([
                [ReportType::FACILITAS, 'Laporan Fasilitas'],
                [ReportType::ASPIRASI, 'Aspirasi'],
                [ReportType::PELANGGARAN, 'Laporan Dugaan Pelanggaran'],
            ])->map(function (array $type) use ($reportTypes, $reports): array {
                $reportType = $reportTypes->firstWhere('code', $type[0]);

                return [
                    'label' => $type[1],
                    'count' => $reportType ? (clone $reports)->where('report_type_id', $reportType->id)->count() : 0,
                ];
            })->all();
            $analytics['unitComparison'] = [
                'labels' => ['Total laporan'],
                'datasets' => collect([
                    [ReportType::UNIT_SARPRAS, 'Sarpras'],
                    [ReportType::UNIT_KEMAHASISWAAN, 'Kemahasiswaan'],
                ])->map(fn (array $unit): array => [
                    'label' => $unit[1],
                    'data' => [(clone $reports)->where('assigned_unit', $unit[0])->count()],
                ])->all(),
            ];
            $analytics['responseByUnit'] = collect([ReportType::UNIT_SARPRAS, ReportType::UNIT_KEMAHASISWAAN])->map(fn (string $unit): array => [
                'label' => $unit === ReportType::UNIT_SARPRAS ? 'Sarpras' : 'Kemahasiswaan',
                'value' => (clone $reports)->where('assigned_unit', $unit)->count(),
            ])->all();

            if (in_array($user->role, [UserRole::ADMIN_SARPRAS, UserRole::SUPER_ADMIN], true)) {
                $analytics['unresolvedAge'] = [
                    ['label' => 'Kurang dari 3 hari', 'count' => (clone $reports)->whereNotIn('status', [Report::STATUS_SELESAI, Report::STATUS_DITOLAK])->where('created_at', '>=', now()->subDays(3))->count()],
                    ['label' => '3 sampai 7 hari', 'count' => (clone $reports)->whereNotIn('status', [Report::STATUS_SELESAI, Report::STATUS_DITOLAK])->whereBetween('created_at', [now()->subDays(7), now()->subDays(3)])->count()],
                    ['label' => 'Lebih dari 7 hari', 'count' => (clone $reports)->whereNotIn('status', [Report::STATUS_SELESAI, Report::STATUS_DITOLAK])->where('created_at', '<', now()->subDays(7))->count()],
                ];
            }

            if (in_array($user->role, [UserRole::ADMIN_KEMAHASISWAAN, UserRole::SUPER_ADMIN], true)) {
                $analytics['categoriesByType'] = $trendTypes->mapWithKeys(fn (ReportType $type) => [$type->code => (clone $reports)
                    ->selectRaw('category_id, COUNT(*) as total')->where('report_type_id', $type->id)->whereNotNull('category_id')
                    ->groupBy('category_id')->with('category:id,name')->orderByDesc('total')->limit(5)->get()
                    ->map(fn (Report $report): array => ['label' => $report->category?->name ?? 'Kategori lain', 'count' => (int) $report->total])->all()])->all();
                $visibilityStatuses = [Report::VISIBILITY_PENDING, Report::VISIBILITY_APPROVED, Report::VISIBILITY_REJECTED];
                $analytics['aspirationVisibility'] = collect($visibilityStatuses)->map(function (string $status) use ($reports, $reportTypes): array {
                    $query = (clone $reports)->where('report_type_id', $reportTypes->firstWhere('code', ReportType::ASPIRASI)?->id)
                        ->where('requested_visibility', Report::VISIBILITY_UMUM)->where('visibility_status', $status);
                    if ($status === Report::VISIBILITY_APPROVED) {
                        $query->where('approved_visibility', Report::VISIBILITY_UMUM);
                    }

                    return [
                        'label' => match ($status) {
                            Report::VISIBILITY_PENDING => 'Menunggu',
                            Report::VISIBILITY_APPROVED => 'Disetujui',
                            Report::VISIBILITY_REJECTED => 'Ditolak',
                        },
                        'count' => $query->count(),
                    ];
                })->all();
                $analytics['violationAnonymity'] = [
                    'anonymous' => (clone $reports)->where('report_type_id', $reportTypes->firstWhere('code', ReportType::PELANGGARAN)?->id)->where('is_anonymous', true)->count(),
                    'identified' => (clone $reports)->where('report_type_id', $reportTypes->firstWhere('code', ReportType::PELANGGARAN)?->id)->where('is_anonymous', false)->count(),
                ];
            }

            if ($user->role === UserRole::SUPER_ADMIN) {
                $analytics['usersByRole'] = collect(UserRole::cases())->map(fn (UserRole $role): array => [
                    'label' => match ($role) {
                        UserRole::MAHASISWA => 'Mahasiswa', UserRole::ADMIN_SARPRAS => 'Admin Sarpras',
                        UserRole::ADMIN_KEMAHASISWAAN => 'Admin Kemahasiswaan', UserRole::SUPER_ADMIN => 'Super Admin',
                    },
                    'value' => User::query()->where('role', $role->value)->where('is_active', true)->count(),
                ])->all();
                $analytics['monthlyLogins'] = [
                    'labels' => $analyticsMonths->map(fn ($month) => $month->translatedFormat('M'))->all(),
                    'values' => $analyticsMonths->map(fn ($month) => ActivityLog::query()->where('action', 'login')
                        ->whereBetween('created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])->count())->all(),
                ];
            }
        }

        $extra = match ($user->role) {
            UserRole::MAHASISWA => [
                'waitingConfirmation' => (clone $reports)->where('status', Report::STATUS_SELESAI)->whereNull('confirmed_at')->count(),
            ],
            UserRole::ADMIN_SARPRAS => [
                'withoutAfter' => (clone $reports)->where('status', Report::STATUS_DIPROSES)
                    ->whereDoesntHave('attachments', fn ($attachments) => $attachments->where('kind', 'after'))->count(),
                'waitingVisibility' => $pendingVisibility,
            ],
            UserRole::ADMIN_KEMAHASISWAAN => [
                'newAspirations' => (clone $reports)->where('status', Report::STATUS_DIKIRIM)
                    ->whereHas('reportType', fn ($types) => $types->where('code', ReportType::ASPIRASI))->count(),
                'newViolations' => (clone $reports)->where('status', Report::STATUS_DIKIRIM)
                    ->whereHas('reportType', fn ($types) => $types->where('code', ReportType::PELANGGARAN))->count(),
                'pendingVisibility' => (clone $reports)->whereIn('status', [Report::STATUS_DIKIRIM, Report::STATUS_DIVERIFIKASI])
                    ->where('requested_visibility', Report::VISIBILITY_UMUM)->where('visibility_status', Report::VISIBILITY_PENDING)->count(),
                'waitingReview' => (clone $reports)->where('status', Report::STATUS_DIKIRIM)->count(),
            ],
            UserRole::SUPER_ADMIN => [
                'activeUsers' => User::query()->where('is_active', true)->count(),
                'latestLogs' => Gate::allows('viewAny', ActivityLog::class)
                    ? ActivityLog::query()->with('user')->latest('created_at')->latest('id')->limit(5)->get()
                    : collect(),
            ],
        };

        return view('livewire.pages.dashboard.'.match ($user->role) {
            UserRole::MAHASISWA => 'mahasiswa',
            UserRole::ADMIN_SARPRAS => 'admin-sarpras',
            UserRole::ADMIN_KEMAHASISWAAN => 'admin-kemahasiswaan',
            UserRole::SUPER_ADMIN => 'super-admin',
        }, compact('user', 'total', 'statusCounts', 'recentReports', 'pendingConfirmation', 'monthly', 'byCategory', 'byLocation', 'byType', 'responseDaysByUnit', 'resolutionDaysByUnit', 'overallResponseDays', 'overallResolutionDays', 'analytics', 'extra'));
    }
}
