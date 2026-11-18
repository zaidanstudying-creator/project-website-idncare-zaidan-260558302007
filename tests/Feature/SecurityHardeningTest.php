<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

test('the Livewire login page renders as a single root with security headers', function (): void {
    $response = $this->get('/login');

    $response->assertOk()
        ->assertSee('Masuk')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

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
