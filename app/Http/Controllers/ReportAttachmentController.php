<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\ReportAttachment;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ReportAttachmentController extends Controller
{
    public function show(ReportAttachment $attachment): Response
    {
        // Archived reports are resolved here only so the normal report policy can authorize the requester.
        $report = Report::withoutGlobalScope('active')->with('reportType')->findOrFail($attachment->report_id);
        Gate::authorize('view', $report);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($attachment->file_path), 404);

        return response($disk->get($attachment->file_path), 200, [
            'Content-Type' => $disk->mimeType($attachment->file_path) ?? 'application/octet-stream',
            'Content-Disposition' => 'inline',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
