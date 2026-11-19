<?php

namespace App\Livewire\Pages\Reports;

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
        $disk = Storage::disk('local');
        $content = $disk->get($attachment->file_path);
        $contentType = $disk->mimeType($attachment->file_path) ?? 'application/octet-stream';

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
        $disk = Storage::disk('local');
        $contentType = $disk->mimeType($attachment->file_path) ?? 'application/octet-stream';
        abort_unless(str_starts_with($contentType, 'image/'), 404);
        $image = 'data:'.$contentType.';base64,'.base64_encode($disk->get($attachment->file_path));

        return view('livewire.pages.reports.attachment-viewer', compact('attachment', 'image'))
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
}
