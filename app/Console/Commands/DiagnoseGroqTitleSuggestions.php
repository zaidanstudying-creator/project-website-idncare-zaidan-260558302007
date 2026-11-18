<?php

namespace App\Console\Commands;

use App\Services\GroqTitleSuggestionService;
use Illuminate\Console\Command;

class DiagnoseGroqTitleSuggestions extends Command
{
    protected $signature = 'groq:diagnose-titles {--timeout=12 : Request timeout in seconds (maximum 60)}';

    protected $description = 'Uji konfigurasi dan koneksi saran judul Groq dengan uraian dummy.';

    public function handle(GroqTitleSuggestionService $groq): int
    {
        $timeout = filter_var($this->option('timeout'), FILTER_VALIDATE_INT);
        if ($timeout === false || $timeout < 1 || $timeout > 60) {
            $this->error('Timeout harus berupa bilangan bulat antara 1 dan 60 detik.');

            return self::FAILURE;
        }

        $key = config('services.groq.key');
        $enabled = (bool) config('services.groq.enabled');
        $keyAvailable = is_string($key) && trim($key) !== '';

        $this->line('Fitur AI aktif: '.($enabled ? 'ya' : 'tidak'));
        $this->line('API key tersedia: '.($keyAvailable ? 'ya' : 'tidak'));

        if (! $enabled || ! $keyAvailable) {
            $this->line('Request tidak dijalankan.');

            return self::FAILURE;
        }

        $startedAt = microtime(true);
        $titles = $groq->suggest(
            'fasilitas',
            'Lampu dummy di ruang kelas berkedip lalu mati; perlu pemeriksaan teknisi.',
            $timeout,
        );
        $duration = microtime(true) - $startedAt;
        $diagnostics = $groq->diagnostics();
        $httpStatus = $diagnostics['http_status'] ?? 'tidak ada';
        $failureType = $diagnostics['failure_type'] ?? 'tidak diketahui';
        $curlCode = $diagnostics['curl_code'] ?? 'tidak ada';

        $this->line(sprintf(
            'HTTP: %s | kategori: %s | cURL: %s | durasi: %.2f dtk | DNS: %s | TCP: %s | TLS: %s | respons_pertama: %s | jumlah_judul: %d',
            $httpStatus,
            $failureType,
            $curlCode,
            $duration,
            $this->formatTiming($diagnostics['dns_sec'] ?? null),
            $this->formatTiming($diagnostics['tcp_sec'] ?? null),
            $this->formatTiming($diagnostics['tls_sec'] ?? null),
            $this->formatTiming($diagnostics['first_byte_sec'] ?? null),
            count($titles),
        ));

        return count($titles) === 3 ? self::SUCCESS : self::FAILURE;
    }

    private function formatTiming(mixed $seconds): string
    {
        return is_numeric($seconds) ? number_format((float) $seconds, 3).' dtk' : 'tidak tersedia';
    }
}
