<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\ReportAttachment;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ReportAttachmentImageController
{
    public function __invoke(ReportAttachment $attachment): Response
    {
        $report = Report::withoutGlobalScope('active')
            ->with('reportType')
            ->findOrFail($attachment->report_id);
        Gate::authorize('view', $report);

        abort_unless(Storage::disk('local')->exists($attachment->file_path), 404);

        $content = Storage::disk('local')->get($attachment->file_path);
        $imageInfo = @getimagesizefromstring($content);
        $contentType = is_array($imageInfo) ? ($imageInfo['mime'] ?? null) : null;

        abort_unless(in_array($contentType, ['image/jpeg', 'image/png'], true), 404);

        return response($content)->header('Content-Type', $contentType);
    }
}
