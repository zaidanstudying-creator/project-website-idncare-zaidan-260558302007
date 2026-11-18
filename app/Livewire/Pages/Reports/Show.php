<?php

namespace App\Livewire\Pages\Reports;

use App\Enums\UserRole;
use App\Models\Report;
use App\Services\ReportLifecycleService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.app', ['showPageHeading' => false])]
class Show extends Component
{
    #[Locked]
    public int $reportId;

    public string $reopenReason = '';

    public function mount(string $report): void
    {
        abort_unless(auth()->user()?->role === UserRole::MAHASISWA, 403);
        $this->reportId = (int) $report;
        $this->report();
    }

    public function confirm(ReportLifecycleService $lifecycle): void
    {
        $report = $this->report();
        Gate::authorize('confirm', $report);
        $lifecycle->confirm($report, auth()->user());
        session()->flash('status', 'Hasil laporan berhasil dikonfirmasi.');
    }

    public function reopen(ReportLifecycleService $lifecycle): void
    {
        $report = $this->report();
        Gate::authorize('reopen', $report);
        $data = $this->validate([
            'reopenReason' => ['required', 'string', 'min:5', 'max:5000'],
        ], [
            'reopenReason.required' => 'Jelaskan alasan membuka kembali laporan.',
            'reopenReason.min' => 'Alasan membuka kembali minimal 5 karakter.',
        ]);
        $lifecycle->reopen($report, auth()->user(), $data['reopenReason']);
        $this->reset('reopenReason');
        session()->flash('status', 'Laporan dibuka kembali dan diteruskan untuk diproses.');
    }

    public function render(): View
    {
        $report = $this->report();

        return view('livewire.pages.reports.show', compact('report'))->title($report->ticket_no);
    }

    private function report(): Report
    {
        $user = auth()->user();
        $report = Report::withoutGlobalScope('active')
            ->whereKey($this->reportId ?? 0)
            ->where(function (Builder $query) use ($user): void {
                $query->where(function (Builder $active) use ($user): void {
                    $active->whereNull('reports.archived_at')
                        ->where(fn (Builder $query) => $query->ownedBy($user)->orWhere(fn (Builder $query) => $query->public()));
                })->orWhere(fn (Builder $archived) => $archived->whereNotNull('reports.archived_at')->ownedBy($user, includeArchived: true));
            })
            ->with(['reportType', 'category', 'location', 'attachments', 'statusHistories.changedBy'])
            ->firstOrFail();
        Gate::authorize('view', $report);

        return $report;
    }
}
