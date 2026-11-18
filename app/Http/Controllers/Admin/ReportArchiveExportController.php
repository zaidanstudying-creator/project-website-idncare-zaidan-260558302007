<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ArchivedReportExportService;
use Symfony\Component\HttpFoundation\Response;

class ReportArchiveExportController extends Controller
{
    public function export(string $format, ArchivedReportExportService $exporter): Response
    {
        abort_unless(in_array($format, ['csv', 'xlsx', 'pdf'], true), 404);

        return $exporter->download($format);
    }
}
