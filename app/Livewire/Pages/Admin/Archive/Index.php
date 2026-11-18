<?php

namespace App\Livewire\Pages\Admin\Archive;

use App\Enums\UserRole;
use App\Models\Report;
use App\Services\ActivityLogService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app', ['showPageHeading' => false])]
class Index extends Component
{
    use WithPagination;

    public string $academicYear = '';

    public string $from = '';

    public string $to = '';

    /** @var array{academic_year: string, from: string, to: string, count: int, key: string}|null */
    #[Locked]
    public ?array $preview = null;

    public function boot(): void
    {
        $user = auth()->user();
        abort_unless($user?->role === UserRole::SUPER_ADMIN, 403);
        abort_unless($user->is_active && session()->get('auth_version') === $user->auth_version && ! $user->must_change_password, 401);
    }

    public function previewArchive(): void
    {
        $data = $this->validate([
            'academicYear' => ['required', 'string', 'max:20', 'regex:/^\d{4}\/\d{4}$/'],
            'from' => ['required', 'date_format:Y-m-d', 'before_or_equal:to'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);
        $count = Report::query()->whereBetween('created_at', [$data['from'].' 00:00:00', $data['to'].' 23:59:59'])->count();

        $this->preview = [
            'academic_year' => $data['academicYear'],
            'from' => $data['from'],
            'to' => $data['to'],
            'count' => $count,
            'key' => $this->confirmationKey($data['academicYear'], $data['from'], $data['to'], $count),
        ];
    }

    public function archiveReports(ActivityLogService $logs): void
    {
        if ($this->preview === null) {
            throw ValidationException::withMessages(['preview' => 'Tampilkan pratinjau arsip sebelum mengonfirmasi.']);
        }

        $preview = $this->preview;
        $currentCount = Report::query()->whereBetween('created_at', [$preview['from'].' 00:00:00', $preview['to'].' 23:59:59'])->count();
        $key = $this->confirmationKey($preview['academic_year'], $preview['from'], $preview['to'], $currentCount);
        if ($currentCount !== $preview['count'] || ! hash_equals($key, $preview['key'])) {
            throw ValidationException::withMessages(['preview' => 'Data berubah atau konfirmasi arsip tidak valid; ulangi pratinjau.']);
        }

        $count = DB::transaction(function () use ($preview, $logs): int {
            $reportIds = Report::query()
                ->whereBetween('created_at', [$preview['from'].' 00:00:00', $preview['to'].' 23:59:59'])
                ->lockForUpdate()
                ->pluck('id');
            $count = $reportIds->count();
            Report::query()->whereIn('id', $reportIds)->update([
                'academic_year' => $preview['academic_year'],
                'archived_at' => now(),
                'updated_at' => now(),
            ]);
            $logs->record(auth()->user(), 'reports_archived', null, [], [
                'academic_year' => $preview['academic_year'],
                'from' => $preview['from'],
                'to' => $preview['to'],
                'count' => $count,
            ]);

            return $count;
        });

        $this->preview = null;
        session()->flash('status', "{$count} laporan diarsipkan. Data dan lampiran tetap tersimpan.");
    }

    public function cancelPreview(): void
    {
        $this->preview = null;
    }

    public function render(): View
    {
        $archives = Report::withoutGlobalScope('active')->whereNotNull('archived_at')
            ->selectRaw('academic_year, COUNT(*) AS report_count, MAX(archived_at) AS archived_at')
            ->groupBy('academic_year')->orderByDesc('academic_year')->get();
        $archivedReports = Report::withoutGlobalScope('active')->whereNotNull('archived_at')
            ->with('reportType:id,code,name')->orderByDesc('archived_at')->orderByDesc('id')->paginate(20);

        return view('livewire.pages.admin.archive.index', compact('archives', 'archivedReports'))
            ->title('Arsip laporan');
    }

    private function confirmationKey(string $academicYear, string $from, string $to, int $count): string
    {
        return hash_hmac('sha256', implode('|', [$academicYear, $from, $to, $count]), (string) config('app.key'));
    }
}
