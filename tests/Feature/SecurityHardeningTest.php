<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

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
        ->assertHeader('Content-Security-Policy-Report-Only');

    $policy = $response->headers->get('Content-Security-Policy-Report-Only');
    preg_match("/script-src 'self' 'nonce-([^']+)'/", (string) $policy, $matches);
    $nonce = $matches[1] ?? null;

    expect($policy)->toContain("default-src 'self'")
        ->toContain("object-src 'none'")
        ->not->toContain("'unsafe-eval'")
        ->and($nonce)->not->toBeNull()
        ->and(config('livewire.csp_safe'))->toBeTrue();

    $response->assertSee('nonce="'.$nonce.'"', false);

    expect(substr_count($response->getContent(), 'class="login-page"'))->toBe(1)
        ->and($response->getContent())->not->toContain('MultipleRootElement');
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
        ->assertHeader('Content-Security-Policy-Report-Only');
    $policy = $response->headers->get('Content-Security-Policy-Report-Only');
    preg_match("/script-src 'self' 'nonce-([^']+)'/", (string) $policy, $matches);
    $nonce = $matches[1] ?? null;

    expect($nonce)->not->toBeNull();

    $response->assertSee('<style nonce="'.$nonce.'">', false)
        ->assertSee('<script nonce="'.$nonce.'">', false);
});
