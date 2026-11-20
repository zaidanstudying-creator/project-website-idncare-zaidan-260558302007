<?php

namespace App\Livewire\Pages\Admin\Reports;

use App\Enums\UserRole;
use App\Models\Report;
use App\Models\ReportType;
use App\Services\ReportStatusService;
use App\Services\ReportVisibilityService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('layouts.app', ['showPageHeading' => false])]
class Show extends Component
{
    use WithFileUploads;

    public int $reportId;

    public string $status = '';

    public string $note = '';

    public mixed $afterPhoto = null;

    public function mount(Report $report): void
    {
        $this->reportId = $report->getKey();
        $this->report();
    }

    public function boot(): void
    {
        $user = auth()->user();
        abort_unless(in_array($user?->role, [UserRole::ADMIN_SARPRAS, UserRole::ADMIN_KEMAHASISWAAN, UserRole::SUPER_ADMIN], true), 403);
        abort_unless($user->is_active && session()->get('auth_version') === $user->auth_version && ! $user->must_change_password, 401);
    }

    public function updateStatus(ReportStatusService $service): void
    {
        $report = $this->report();
        Gate::authorize('update', $report);
        $allowedStatuses = match ($report->status) {
            Report::STATUS_DIKIRIM => [Report::STATUS_DIVERIFIKASI, Report::STATUS_DITOLAK],
            Report::STATUS_DIVERIFIKASI => [Report::STATUS_DIPROSES, Report::STATUS_DITOLAK],
            Report::STATUS_DIPROSES => [Report::STATUS_SELESAI, Report::STATUS_DITOLAK],
            default => [],
        };
        $isFacility = $report->reportType->code === ReportType::FACILITAS;
        $data = $this->validate([
            'status' => ['required', 'string', Rule::in($allowedStatuses)],
            'note' => ['nullable', 'string', 'max:5000', Rule::requiredIf($this->status === Report::STATUS_DITOLAK)],
            'afterPhoto' => $isFacility
                ? ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:4096']
                : ['prohibited'],
        ], [
            'status.in' => 'Status yang dipilih tidak mengikuti alur penanganan laporan.',
            'note.required' => 'Alasan wajib diisi saat laporan ditolak.',
            'afterPhoto.image' => 'Lampiran sesudah harus berupa gambar.',
            'afterPhoto.mimes' => 'Foto sesudah harus berformat JPG, JPEG, atau PNG.',
            'afterPhoto.max' => 'Ukuran foto sesudah maksimal 4 MB.',
        ]);

        $service->update($report, auth()->user(), [
            'status' => $data['status'],
            'note' => $data['note'] ?? null,
            'after_photo' => $data['afterPhoto'] ?? null,
        ]);

        $this->reset(['status', 'note', 'afterPhoto']);
        $this->dispatch('app-notification', message: 'Perubahan laporan berhasil disimpan.', type: 'success');
    }

    public function decideVisibility(string $decision, ReportVisibilityService $service): void
    {
        $report = $this->report();
        Gate::authorize('manageVisibility', $report);
        $validated = validator(['decision' => $decision], [
            'decision' => ['required', 'string', Rule::in(['approve', 'reject'])],
        ])->validate();
        $service->decide($report, auth()->user(), $validated['decision']);
        $this->dispatch('app-notification', message: $decision === 'approve'
            ? 'Permintaan visibilitas umum disetujui.'
            : 'Permintaan visibilitas umum ditolak.', type: 'success');
    }

    public function render(): View
    {
        $report = $this->report();
        $nextStatuses = match ($report->status) {
            Report::STATUS_DIKIRIM => [Report::STATUS_DIVERIFIKASI, Report::STATUS_DITOLAK],
            Report::STATUS_DIVERIFIKASI => [Report::STATUS_DIPROSES, Report::STATUS_DITOLAK],
            Report::STATUS_DIPROSES => [Report::STATUS_SELESAI, Report::STATUS_DITOLAK],
            default => [],
        };

        return view('livewire.pages.admin.reports.show', compact('report', 'nextStatuses'))
            ->title($report->ticket_no);
    }

    private function report(): Report
    {
        $report = Report::query()->visibleTo(auth()->user())
            ->with(['reportType', 'category', 'location', 'attachments', 'statusHistories.changedBy'])
            ->findOrFail($this->reportId ?? 0);
        Gate::authorize('view', $report);

        // Token-linked violations never resolve the reporter relation in admin UI.
        if ($report->reporter_token === null && $report->reporter_id !== null) {
            $report->load('reporter');
        }

        return $report;
    }
}
