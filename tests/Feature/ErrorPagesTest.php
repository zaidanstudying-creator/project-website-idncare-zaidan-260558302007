<?php

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

it('renders the Indonesian not found page with an actual 404 status', function (): void {
    config(['app.debug' => false]);

    $response = app(ExceptionHandler::class)->render(
        Request::create('/alamat-tidak-tersedia', 'GET', server: ['HTTP_ACCEPT' => 'text/html']),
        new HttpException(404),
    );

    expect($response->getStatusCode())->toBe(404);
    $this->assertStringContainsString('Halaman tidak ditemukan', $response->getContent());
    $this->assertStringContainsString('Kembali ke beranda', $response->getContent());
    $this->assertStringContainsString('Masuk', $response->getContent());
});

it('renders the supported HTTP error pages with their actual status codes', function (int $status, string $title): void {
    config(['app.debug' => false]);

    $response = app(ExceptionHandler::class)->render(
        Request::create('/contoh-error', 'GET', server: ['HTTP_ACCEPT' => 'text/html']),
        new HttpException($status),
    );

    expect($response->getStatusCode())->toBe($status);
    $this->assertStringContainsString($title, $response->getContent());
})->with([
    'unauthorized' => [401, 'Silakan masuk untuk melanjutkan'],
    'forbidden' => [403, 'Kamu tidak memiliki izin'],
    'session expired' => [419, 'Sesi kamu telah berakhir'],
    'too many requests' => [429, 'Terlalu banyak permintaan'],
    'unprocessable content' => [422, 'Data belum dapat diproses'],
    'service unavailable' => [503, 'Layanan sedang tidak tersedia'],
]);

it('keeps JSON exception responses as JSON instead of rendering HTML error pages', function (): void {
    config(['app.debug' => false]);

    $response = app(ExceptionHandler::class)->render(
        Request::create('/api/resource', 'GET', server: ['HTTP_ACCEPT' => 'application/json']),
        new HttpException(404),
    );

    expect($response->getStatusCode())->toBe(404);
    expect($response->headers->get('Content-Type'))->toContain('application/json');
    $this->assertStringNotContainsString('Halaman tidak ditemukan', $response->getContent());
});

it('renders a generic Indonesian server error without exposing exception details', function (): void {
    config(['app.debug' => false]);
    $privateDiagnostic = 'private diagnostic marker';
    $privateQueryValue = 'query-secret-marker';

    $response = app(ExceptionHandler::class)->render(
        Request::create('/contoh-error?filter='.$privateQueryValue, 'GET', server: ['HTTP_ACCEPT' => 'text/html']),
        new RuntimeException($privateDiagnostic),
    );

    expect($response->getStatusCode())->toBe(500);
    $this->assertStringContainsString('Terjadi kesalahan pada server', $response->getContent());
    $this->assertStringContainsString('Ke beranda', $response->getContent());
    $this->assertStringNotContainsString('/contoh-error', $response->getContent());
    $this->assertStringNotContainsString($privateDiagnostic, $response->getContent());
    $this->assertStringNotContainsString($privateQueryValue, $response->getContent());
    $this->assertStringNotContainsString('RuntimeException', $response->getContent());
});
