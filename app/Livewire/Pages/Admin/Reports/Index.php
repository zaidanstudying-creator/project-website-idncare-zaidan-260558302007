<?php

namespace App\Livewire\Pages\Admin\Reports;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Location;
use App\Models\Report;
use App\Models\ReportType;
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

    #[Url]
    public string $status = '';

    #[Url]
    public string $type = '';

    #[Url]
    public string $categoryId = '';

    #[Url]
    public string $locationId = '';

    #[Url(as: 'q')]
    public string $search = '';

    public function updated(string $property): void
    {
        if (in_array($property, ['status', 'type', 'categoryId', 'locationId', 'search'], true)) {
            $this->resetPage();
        }
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['status', 'type', 'categoryId', 'locationId', 'search']);
        $this->resetPage();
    }

    public function render(): View
    {
        Gate::authorize('viewAny', Report::class);
        $user = auth()->user();
        $allowedTypes = match ($user->role) {
            UserRole::ADMIN_SARPRAS => [ReportType::FACILITAS],
            UserRole::ADMIN_KEMAHASISWAAN => [ReportType::ASPIRASI, ReportType::PELANGGARAN],
            default => [ReportType::FACILITAS, ReportType::ASPIRASI, ReportType::PELANGGARAN],
        };
        $filters = validator([
            'status' => $this->status !== '' ? $this->status : null,
            'type' => $this->type !== '' ? $this->type : null,
            'categoryId' => $this->categoryId !== '' ? $this->categoryId : null,
            'locationId' => $this->locationId !== '' ? $this->locationId : null,
            'search' => $this->search,
        ], [
            'status' => ['nullable', Rule::in([Report::STATUS_DIKIRIM, Report::STATUS_DIVERIFIKASI, Report::STATUS_DIPROSES, Report::STATUS_SELESAI, Report::STATUS_DITOLAK])],
            'type' => ['nullable', Rule::in($allowedTypes)],
            'categoryId' => ['nullable', 'integer'],
            'locationId' => ['nullable', 'integer'],
            'search' => ['string', 'max:100'],
        ])->validate();
        $search = trim($filters['search']);
        $baseQuery = Report::query()->visibleTo($user);
        $reports = (clone $baseQuery)
            ->with(['reportType', 'category', 'location'])
            ->when($filters['status'], fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['type'], fn (Builder $query, string $type) => $query->whereHas('reportType', fn (Builder $types) => $types->where('code', $type)))
            ->when($filters['categoryId'], fn (Builder $query, string $categoryId) => $query->where('category_id', (int) $categoryId))
            ->when($filters['locationId'], fn (Builder $query, string $locationId) => $query->where('location_id', (int) $locationId))
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('ticket_no', 'like', '%'.$search.'%')->orWhere('title', 'like', '%'.$search.'%')))
            ->orderByDesc('created_at')->orderByDesc('id')->paginate(10)->onEachSide(1);

        // Do not resolve reporter relationships for token-linked anonymous violation reports.
        $reports->getCollection()
            ->filter(fn (Report $report): bool => $report->reporter_token === null)
            ->load('reporter');

        $categories = Category::query()->whereHas('reportType', fn (Builder $query) => $query->whereIn('code', $allowedTypes))
            ->orderBy('name')->get();
        $locations = Location::query()->orderBy('name')->get();
        $counts = [
            'total' => (clone $baseQuery)->count(),
            'dikirim' => (clone $baseQuery)->where('status', Report::STATUS_DIKIRIM)->count(),
            'diproses' => (clone $baseQuery)->where('status', Report::STATUS_DIPROSES)->count(),
            'selesai' => (clone $baseQuery)->where('status', Report::STATUS_SELESAI)->count(),
        ];

        return view('livewire.pages.admin.reports.index', compact(
            'reports', 'categories', 'locations', 'counts', 'allowedTypes',
        ))->title('Penanganan laporan');
    }
}
