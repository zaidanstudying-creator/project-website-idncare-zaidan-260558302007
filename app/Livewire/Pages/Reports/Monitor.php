<?php

namespace App\Livewire\Pages\Reports;

use App\Models\Report;
use App\Models\ReportType;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['showPageHeading' => false])]
class Monitor extends Component
{
    use WithPagination;

    #[Url(as: 'type')]
    public string $type = '';

    #[Url(as: 'q')]
    public string $search = '';

    public function updated(string $property): void
    {
        if (in_array($property, ['type', 'search'], true)) {
            $this->resetPage();
        }
    }

    public function setType(string $type): void
    {
        $this->type = $type;
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['type', 'search']);
        $this->resetPage();
    }

    public function render(): View
    {
        $allowedTypes = [ReportType::FACILITAS, ReportType::ASPIRASI];
        $filters = validator([
            'type' => $this->type !== '' ? $this->type : null,
            'search' => $this->search,
        ], [
            'type' => ['nullable', Rule::in($allowedTypes)],
            'search' => ['string', 'max:100'],
        ])->validate();
        $search = trim($filters['search']);
        $reports = Report::query()->public()
            ->select([
                'reports.id', 'reports.ticket_no', 'reports.report_type_id', 'reports.category_id', 'reports.location_id',
                'reports.title', 'reports.status', 'reports.is_anonymous', 'reports.created_at', 'reports.updated_at',
            ])
            ->with(['reportType', 'category', 'location'])
            ->addSelect(['public_reporter_name' => User::query()->select('name')
                ->whereColumn('users.id', 'reports.reporter_id')->where('reports.is_anonymous', false)])
            ->when($filters['type'], fn (Builder $query, string $type) => $query->whereHas('reportType', fn (Builder $types) => $types->where('code', $type)))
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('ticket_no', 'like', '%'.$search.'%')->orWhere('title', 'like', '%'.$search.'%')))
            ->orderByDesc('created_at')->orderByDesc('id')->paginate(12);
        $counts = [
            'total' => Report::query()->public()->count(),
            'fasilitas' => Report::query()->public()->whereHas('reportType', fn (Builder $query) => $query->where('code', ReportType::FACILITAS))->count(),
            'aspirasi' => Report::query()->public()->whereHas('reportType', fn (Builder $query) => $query->where('code', ReportType::ASPIRASI))->count(),
        ];

        return view('livewire.pages.reports.monitor', compact('reports', 'counts'))->title('Pantau laporan umum');
    }
}
