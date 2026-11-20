<?php

namespace App\Livewire\Pages\Admin\ActivityLogs;

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Report;
use App\Models\ReportType;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['showPageHeading' => false])]
class Index extends Component
{
    use WithPagination;

    public string $action = '';

    public string $from = '';

    public string $to = '';

    public function updated(string $property): void
    {
        if (in_array($property, ['action', 'from', 'to'], true)) {
            $this->resetPage();
        }
    }

    public function resetFilters(): void
    {
        $this->reset(['action', 'from', 'to']);
        $this->resetPage();
    }

    public function render(): View
    {
        Gate::authorize('viewAny', ActivityLog::class);
        $filters = validator([
            'action' => $this->action,
            'from' => $this->from,
            'to' => $this->to,
        ], [
            'action' => ['nullable', 'string', 'max:100'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ])->validate();

        $user = auth()->user();
        $query = ActivityLog::query()->with('user');

        if ($user->role === UserRole::ADMIN_SARPRAS) {
            $this->limitToUnit($query, ReportType::UNIT_SARPRAS, [ReportType::FACILITAS]);
        } elseif ($user->role === UserRole::ADMIN_KEMAHASISWAAN) {
            $this->limitToUnit($query, ReportType::UNIT_KEMAHASISWAAN, [ReportType::ASPIRASI, ReportType::PELANGGARAN]);
        }

        $accessibleLogsCount = (clone $query)->count();

        $query->when($filters['action'] ?? null, fn (Builder $query, string $action) => $query->where('action', $action))
            ->when($filters['from'] ?? null, fn (Builder $query, string $from) => $query->whereDate('created_at', '>=', $from))
            ->when($filters['to'] ?? null, fn (Builder $query, string $to) => $query->whereDate('created_at', '<=', $to));

        $actions = (clone $query)->reorder()->select('action')->distinct()->orderBy('action')->pluck('action');
        $logs = $query->orderByDesc('created_at')->orderByDesc('id')->paginate(20)->onEachSide(1);

        return view('livewire.pages.admin.activity-logs.index', compact('logs', 'actions', 'accessibleLogsCount'))
            ->title('Activity log');
    }

    private function limitToUnit(Builder $query, string $unit, array $typeCodes): void
    {
        $reportIds = Report::query()->where('assigned_unit', $unit)->select('id');
        $categoryIds = Category::query()
            ->whereHas('reportType', fn (Builder $types) => $types->whereIn('code', $typeCodes))
            ->select('id');

        $query->where(function (Builder $logs) use ($reportIds, $categoryIds): void {
            $logs->where(fn (Builder $reports) => $reports
                ->where('subject_type', (new Report)->getMorphClass())
                ->whereIn('subject_id', $reportIds))
                ->orWhere(fn (Builder $categories) => $categories
                    ->where('subject_type', (new Category)->getMorphClass())
                    ->whereIn('subject_id', $categoryIds))
                ->orWhere('subject_type', 'App\\Models\\Location');
        });
    }
}
