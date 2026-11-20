<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the account import page for a super admin', function (): void {
    $admin = User::factory()->create([
        'role' => UserRole::SUPER_ADMIN,
        'must_change_password' => false,
    ]);

    $this->actingAs($admin)
        ->withSession(['auth_version' => $admin->auth_version])
        ->get(route('admin.users.import'))
        ->assertOk()
        ->assertSee('Impor akun mahasiswa')
        ->assertSee('Pilih berkas');
});
