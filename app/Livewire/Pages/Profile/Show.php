<?php

namespace App\Livewire\Pages\Profile;

use App\Enums\UserRole;
use App\Models\Report;
use App\Models\ReportType;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app', ['showPageHeading' => false])]
class Show extends Component
{
    public function render(): View
    {
        $user = auth()->user();
        $recentLogins = $user->activityLogs()->where('action', 'login')->latest('created_at')->limit(5)->get();
        $profileSummary = $this->summaryFor($user);

        return view('livewire.pages.profile.show', compact('user', 'recentLogins', 'profileSummary'))->title('Profil');
    }

    /**
     * Profile account fields are maintained by administrators; this summary only reports
     * figures visible to the signed-in role and never includes anonymous reporter identity.
     *
     * @return array{title: string, items: array<int, array{label: string, value: int|float|null}>, note: string|null}
     */
    private function summaryFor(User $user): array
    {
        return match ($user->role) {
            UserRole::MAHASISWA => $this->studentSummary($user),
            UserRole::ADMIN_SARPRAS => $this->unitAdminSummary($user, ReportType::UNIT_SARPRAS),
            UserRole::ADMIN_KEMAHASISWAAN => $this->studentAffairsSummary($user),
            UserRole::SUPER_ADMIN => $this->superAdminSummary(),
        };
    }

    private function studentSummary(User $user): array
    {
        $reports = Report::query()->ownedBy($user, includeArchived: true);

        return [
            'title' => 'Ringkasan laporanku',
            'items' => [
                ['label' => 'Laporan saya', 'value' => (clone $reports)->count()],
                ['label' => 'Menunggu konfirmasimu', 'value' => (clone $reports)->where('status', Report::STATUS_SELESAI)->whereNull('confirmed_at')->count()],
                ['label' => 'Terkonfirmasi', 'value' => (clone $reports)->whereNotNull('confirmed_at')->count()],
            ],
            'note' => 'Laporan anonim tetap bisa kamu lihat sendiri di halaman Laporan saya.',
        ];
    }

    private function unitAdminSummary(User $user, string $unit): array
    {
        $reports = Report::query()->visibleTo($user)->where('assigned_unit', $unit);
        $completed = (clone $reports)->whereNotNull('completed_at')->get(['created_at', 'completed_at']);
        $averageResolutionDays = $completed->isEmpty()
            ? null
            : round($completed->avg(fn (Report $report): float => $report->created_at->diffInSeconds($report->completed_at) / 86400), 1);

        return [
            'title' => 'Ringkasan penanganan',
            'items' => [
                ['label' => 'Laporan ditangani', 'value' => (clone $reports)->count()],
                ['label' => 'Selesai', 'value' => (clone $reports)->where('status', Report::STATUS_SELESAI)->count()],
                ['label' => 'Rata-rata penyelesaian (hari)', 'value' => $averageResolutionDays],
            ],
            'note' => null,
        ];
    }

    private function studentAffairsSummary(User $user): array
    {
        $reports = Report::query()->visibleTo($user);
        $completed = (clone $reports)->whereNotNull('completed_at')->get(['created_at', 'completed_at']);
        $averageResolutionDays = $completed->isEmpty()
            ? null
            : round($completed->avg(fn (Report $report): float => $report->created_at->diffInSeconds($report->completed_at) / 86400), 1);

        return [
            'title' => 'Ringkasan penanganan',
            'items' => [
                ['label' => 'Aspirasi ditangani', 'value' => (clone $reports)->whereHas('reportType', fn ($query) => $query->where('code', ReportType::ASPIRASI))->count()],
                ['label' => 'Pelanggaran ditangani', 'value' => (clone $reports)->whereHas('reportType', fn ($query) => $query->where('code', ReportType::PELANGGARAN))->count()],
                ['label' => 'Rata-rata penyelesaian (hari)', 'value' => $averageResolutionDays],
            ],
            'note' => 'Identitas pelapor anonim pada laporan pelanggaran tidak dapat dibuka siapa pun.',
        ];
    }

    private function superAdminSummary(): array
    {
        return [
            'title' => 'Ringkasan pengelolaan',
            'items' => [
                ['label' => 'Akun dikelola', 'value' => User::query()->count()],
                ['label' => 'Total laporan', 'value' => Report::withoutGlobalScope('active')->count()],
                ['label' => 'Tahun ajaran terarsip', 'value' => Report::withoutGlobalScope('active')->whereNotNull('archived_at')->whereNotNull('academic_year')->distinct()->count('academic_year')],
            ],
            'note' => 'Identitas pelapor anonim pada laporan pelanggaran tidak dapat dibuka siapa pun, termasuk Super Admin.',
        ];
    }
}
