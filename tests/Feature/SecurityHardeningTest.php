<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Vite;

uses(RefreshDatabase::class);

test('isolates test credentials and database from the application environment', function (): void {
    expect(app()->environment())->toBe('testing')
        ->and(config('database.default'))->toBe('sqlite')
        ->and(config('database.connections.sqlite.database'))->toBe(':memory:')
        ->and(config('app.key'))->toBe('base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=')
        ->and(config('app.report_key'))->toBe(str_repeat('a', 64));
});

test('the Livewire login page renders as a single root with security headers', function (): void {
    $response = $this->get('/login');

    $response->assertOk()
        ->assertSee('Masuk')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Content-Security-Policy')
        ->assertHeaderMissing('Content-Security-Policy-Report-Only');

    $policy = $response->headers->get('Content-Security-Policy');
    preg_match("/script-src 'self' 'nonce-([^']+)'/", (string) $policy, $matches);
    $nonce = $matches[1] ?? null;

    expect($policy)->toContain("default-src 'self'")
        ->toContain("object-src 'none'")
        ->toContain("connect-src 'self'")
        ->toContain("style-src-attr 'unsafe-inline'")
        ->not->toContain("'unsafe-eval'")
        ->not->toContain('ws://')
        ->and($nonce)->not->toBeNull()
        ->and(config('livewire.csp_safe'))->toBeTrue();

    $response->assertSee('nonce="'.$nonce.'"', false);

    expect(substr_count($response->getContent(), 'class="login-page"'))->toBe(1)
        ->and($response->getContent())->not->toContain('MultipleRootElement');
});

test('allows Vite websocket connections only when the local dev server is active', function (): void {
    $hotFile = tempnam(sys_get_temp_dir(), 'idn-care-vite-hot-');

    try {
        file_put_contents($hotFile, 'http://[::1]:5174');
        app()->detectEnvironment(fn (): string => 'local');
        Vite::useHotFile($hotFile);

        $response = $this->get('/login')->assertOk();
        $policy = $response->headers->get('Content-Security-Policy');

        expect($policy)->toContain("connect-src 'self' ws: wss:");
    } finally {
        Vite::useHotFile(public_path('hot'));
        unlink($hotFile);
    }
});

test('does not allow Vite websocket connections in production', function (): void {
    $hotFile = tempnam(sys_get_temp_dir(), 'idn-care-vite-hot-');

    try {
        file_put_contents($hotFile, 'http://localhost:5174');
        app()->detectEnvironment(fn (): string => 'production');
        Vite::useHotFile($hotFile);

        $response = $this->get('/login')->assertOk();
        $policy = $response->headers->get('Content-Security-Policy');

        expect($policy)->toContain("connect-src 'self'")
            ->not->toContain('ws:')
            ->not->toContain('wss:');
    } finally {
        Vite::useHotFile(public_path('hot'));
        unlink($hotFile);
    }
});

test('passwords are stored as hashes and do not match their stored representation', function (): void {
    $user = User::factory()->create([
        'password' => 'temporary-password',
    ]);

    expect(Hash::check('temporary-password', $user->password))->toBeTrue()
        ->and($user->password)->not->toBe('temporary-password');
});

test('adds the CSP nonce to inline assets on error pages', function (): void {
    config(['app.debug' => false]);

    $response = $this->get('/missing-page-for-csp')
        ->assertNotFound()
        ->assertHeader('Content-Security-Policy')
        ->assertHeaderMissing('Content-Security-Policy-Report-Only');
    $policy = $response->headers->get('Content-Security-Policy');
    preg_match("/script-src 'self' 'nonce-([^']+)'/", (string) $policy, $matches);
    $nonce = $matches[1] ?? null;

    expect($nonce)->not->toBeNull();

    $response->assertSee('<style nonce="'.$nonce.'">', false)
        ->assertSee('<script nonce="'.$nonce.'">', false);
});
