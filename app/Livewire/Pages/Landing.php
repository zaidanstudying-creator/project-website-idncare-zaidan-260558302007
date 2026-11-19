<?php

namespace App\Livewire\Pages;

use App\Enums\UserRole;
use App\Models\Report;
use App\Models\ReportType;
use App\Models\SiteSetting;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.public', ['description' => 'IDN CARE, portal resmi pelaporan dan aspirasi kampus.'])]
class Landing extends Component
{
    public function render(): View
    {
        $user = auth()->user();
        $enabledReportTypes = SiteSetting::current()?->reportTypeAvailability() ?? [
            ReportType::FACILITAS => true,
            ReportType::ASPIRASI => true,
            ReportType::PELANGGARAN => true,
        ];
        $publicReports = Report::query()->public();

        if (in_array($user?->role, [UserRole::ADMIN_SARPRAS, UserRole::ADMIN_KEMAHASISWAAN], true)) {
            $publicReports->visibleTo($user);
        }

        $latestReports = $user
            ? $publicReports->with(['reportType', 'category', 'location'])->latest()->limit(3)->get()
            : collect();
        $landingSummary = null;

        if ($user) {
            $ownedReports = match ($user->role) {
                UserRole::MAHASISWA => Report::ownedBy($user),
                UserRole::ADMIN_SARPRAS, UserRole::ADMIN_KEMAHASISWAAN => Report::visibleTo($user),
                UserRole::SUPER_ADMIN => Report::query(),
            };

            $landingSummary = [
                'total' => (clone $ownedReports)->count(),
                'new' => (clone $ownedReports)->where('status', Report::STATUS_DIKIRIM)->count(),
                'waiting' => (clone $ownedReports)->where('status', Report::STATUS_SELESAI)->whereNull('confirmed_at')->count(),
                'processing' => (clone $ownedReports)->where('status', Report::STATUS_DIPROSES)->count(),
            ];
        }

        return view('livewire.pages.landing', compact('user', 'latestReports', 'landingSummary', 'enabledReportTypes'))->title('Suarakan, laporkan, pantau');
    }
}
