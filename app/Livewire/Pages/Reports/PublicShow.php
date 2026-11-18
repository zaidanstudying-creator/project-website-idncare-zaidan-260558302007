<?php

namespace App\Livewire\Pages\Reports;

use App\Enums\UserRole;
use App\Models\Report;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.app', ['showPageHeading' => false])]
class PublicShow extends Component
{
    #[Locked]
    public int $reportId;

    public function mount(string $report): void
    {
        abort_unless(auth()->user()?->role === UserRole::MAHASISWA, 403);
        $this->reportId = (int) $report;
        $this->report();
    }

    public function render(): View
    {
        $report = $this->report();

        return view('livewire.pages.reports.public-show', compact('report'))->title($report->ticket_no);
    }

    private function report(): Report
    {
        $report = Report::query()->public()->whereKey($this->reportId ?? 0)
            ->with('attachments')->firstOrFail();
        Gate::authorize('view', $report);

        return $report;
    }
}
