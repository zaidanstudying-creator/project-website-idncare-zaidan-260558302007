<?php

namespace App\Livewire\Pages\Reports;

use App\Enums\UserRole;
use App\Models\Report;
use App\Models\ReportAttachment;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.app', ['showPageHeading' => false])]
class AttachmentViewer extends Component
{
    #[Locked]
    public int $attachmentId;

    public function mount(int $attachment): void
    {
        $this->attachmentId = $attachment;
        $this->attachment();
    }

    public function download(): mixed
    {
        $attachment = $this->attachment();
        [$contentType, $content] = $this->imageContent($attachment);

        return response()->streamDownload(function () use ($content): void {
            echo $content;
        }, basename($attachment->file_path), [
            'Content-Type' => $contentType,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function render(): View
    {
        $attachment = $this->attachment();
        [$contentType, $content] = $this->imageContent($attachment);
        $image = 'data:'.$contentType.';base64,'.base64_encode($content);
        $backUrl = auth()->user()?->role === UserRole::MAHASISWA
            ? route('reports.show', $attachment->report_id)
            : route('admin.reports.show', $attachment->report_id);

        return view('livewire.pages.reports.attachment-viewer', compact('attachment', 'backUrl', 'image'))
            ->title('Lampiran laporan');
    }

    private function attachment(): ReportAttachment
    {
        $attachment = ReportAttachment::query()->findOrFail($this->attachmentId ?? 0);
        $report = Report::withoutGlobalScope('active')->with('reportType')->findOrFail($attachment->report_id);
        Gate::authorize('view', $report);
        abort_unless(Storage::disk('local')->exists($attachment->file_path), 404);

        return $attachment;
    }

    /** @return array{0: 'image/jpeg'|'image/png', 1: string} */
    private function imageContent(ReportAttachment $attachment): array
    {
        $content = Storage::disk('local')->get($attachment->file_path);
        $imageInfo = @getimagesizefromstring($content);
        $contentType = is_array($imageInfo) ? ($imageInfo['mime'] ?? null) : null;

        abort_unless(in_array($contentType, ['image/jpeg', 'image/png'], true), 404);

        return [$contentType, $content];
    }
}
