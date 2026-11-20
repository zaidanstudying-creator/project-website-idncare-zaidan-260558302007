<?php

namespace App\Livewire\Pages\Reports;

use App\Enums\UserRole;
use App\Models\Report;
use App\Models\ReportType;
use App\Services\ReportLifecycleService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['showPageHeading' => false])]
class Index extends Component
{
    use WithPagination;

    #[Url(as: 'type')]
    public string $typeFilter = '';

    #[Url(as: 'status')]
    public string $statusFilter = '';

    #[Url(as: 'q')]
    public string $search = '';

    public function updated(string $property): void
    {
        if (in_array($property, ['typeFilter', 'statusFilter', 'search'], true)) {
            $this->resetPage();
        }
    }

    public function setType(string $type): void
    {
        $this->typeFilter = $type;
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['typeFilter', 'statusFilter', 'search']);
        $this->resetPage();
    }

    public function confirmReport(int $reportId, ReportLifecycleService $lifecycle): void
    {
        $report = Report::query()->ownedBy(auth()->user(), includeArchived: true)->findOrFail($reportId);
        Gate::authorize('confirm', $report);
        $lifecycle->confirm($report, auth()->user());
        $this->dispatch('app-notification', message: 'Hasil laporan berhasil dikonfirmasi.', type: 'success');
    }

    public function render(): View
    {
        $user = auth()->user();
        abort_unless($user?->role === UserRole::MAHASISWA, 403);
        Gate::authorize('viewAny', Report::class);
        $typeCodes = [ReportType::FACILITAS, ReportType::ASPIRASI, ReportType::PELANGGARAN];
        $statuses = [Report::STATUS_DIKIRIM, Report::STATUS_DIVERIFIKASI, Report::STATUS_DIPROSES, Report::STATUS_SELESAI, Report::STATUS_DITOLAK];
        $filters = validator([
            'type' => $this->typeFilter !== '' ? $this->typeFilter : null,
            'status' => $this->statusFilter !== '' ? $this->statusFilter : null,
            'search' => $this->search,
        ], [
            'type' => ['nullable', Rule::in($typeCodes)],
            'status' => ['nullable', Rule::in($statuses)],
            'search' => ['string', 'max:100'],
        ])->validate();

        $search = trim($filters['search']);
        $ownedReports = Report::query()->ownedBy($user, includeArchived: true);
        $reports = (clone $ownedReports)
            ->with(['reportType', 'category'])->withCount('attachments')
            ->when($filters['type'], fn (Builder $query, string $type) => $query->whereHas('reportType', fn (Builder $types) => $types->where('code', $type)))
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('ticket_no', 'like', '%'.$search.'%')->orWhere('title', 'like', '%'.$search.'%')))
            ->orderByDesc('created_at')->orderByDesc('id')->paginate(10)->onEachSide(1);

        $counts = [
            'total' => (clone $ownedReports)->count(),
            'dikirim' => (clone $ownedReports)->where('status', Report::STATUS_DIKIRIM)->count(),
            'diproses' => (clone $ownedReports)->where('status', Report::STATUS_DIPROSES)->count(),
            'selesai' => (clone $ownedReports)->where('status', Report::STATUS_SELESAI)->count(),
        ];
        $typeCounts = collect($typeCodes)->mapWithKeys(fn (string $code): array => [
            $code => (clone $ownedReports)->whereHas('reportType', fn (Builder $query) => $query->where('code', $code))->count(),
        ]);

        return view('livewire.pages.reports.index', compact('reports', 'counts', 'typeCounts'))
            ->title('Laporan saya');
    }
}
