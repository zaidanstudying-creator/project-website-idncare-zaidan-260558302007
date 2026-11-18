<?php

namespace App\Http\Controllers;

use App\Http\Requests\SuggestReportTitleRequest;
use App\Services\GroqTitleSuggestionService;
use Illuminate\Http\JsonResponse;

class AiTitleSuggestionController extends Controller
{
    public function __invoke(SuggestReportTitleRequest $request, GroqTitleSuggestionService $groq): JsonResponse
    {
        $titles = $groq->suggest(
            $request->validated('type'),
            $request->validated('description'),
        );
        $success = count($titles) === 3;
        $diagnostics = $groq->diagnostics();
        $status = $diagnostics['http_status'] ?? null;
        $rateLimited = ! $success && $status === 429;
        $authenticationFailed = ! $success && in_array($status, [401, 403], true);
        $timedOut = ! $success && ($diagnostics['failure_type'] ?? null) === 'timeout';

        return response()->json([
            'titles' => $titles,
            'message' => $success
                ? null
                : ($rateLimited
                    ? 'Kuota layanan saran judul sedang terbatas. Coba lagi nanti atau tulis judul secara manual.'
                    : ($authenticationFailed
                        ? 'Layanan saran judul belum dapat diakses. Silakan tulis judul secara manual; administrator perlu memeriksa konfigurasi layanan.'
                        : ($timedOut
                            ? 'Layanan saran judul merespons terlalu lama. Kamu tetap bisa menulis judul dan mengirim laporan.'
                            : 'Saran AI tidak tersedia saat ini. Kamu tetap bisa menulis judul dan mengirim laporan.'))),
        ], $success ? 200 : ($rateLimited ? 429 : 503));
    }
}
