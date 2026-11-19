<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('opens the account management page for a super admin', function (): void {
    $admin = User::factory()->create([
        'role' => UserRole::SUPER_ADMIN,
        'must_change_password' => false,
    ]);

    $this->actingAs($admin)
        ->withSession(['auth_version' => $admin->auth_version])
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('Daftar akun')
        ->assertSee('Cari ID atau nama')
        ->assertSee('idn-loading-spinner');
});

it('lets super admins search and filter accounts without leaving the account page', function (): void {
    $admin = User::factory()->create([
        'role' => UserRole::SUPER_ADMIN,
        'must_change_password' => false,
    ]);
    $matchingStudent = User::factory()->create([
        'user_code' => 'MHS-LIVE-7',
        'name' => 'Mahasiswa Tersaring',
        'role' => UserRole::MAHASISWA,
        'is_active' => true,
    ]);
    User::factory()->create([
        'user_code' => 'MHS-LIVE-8',
        'name' => 'Mahasiswa Nonaktif',
        'role' => UserRole::MAHASISWA,
        'is_active' => false,
    ]);
    User::factory()->create([
        'user_code' => 'ADM-LIVE-9',
        'name' => 'Admin Sarpras',
        'role' => UserRole::ADMIN_SARPRAS,
        'is_active' => true,
    ]);

    $this->actingAs($admin)->withSession(['auth_version' => $admin->auth_version]);

    Livewire::test('components.admin.user-table')
        ->assertSee($matchingStudent->user_code)
        ->assertSee('Edit informasi mahasiswa')
        ->assertSee('Mahasiswa Nonaktif')
        ->assertSee('Admin Sarpras')
        ->set('search', 'MHS-LIVE-7')
        ->assertSee($matchingStudent->user_code)
        ->assertDontSee('MHS-LIVE-8')
        ->assertDontSee('ADM-LIVE-9')
        ->set('search', '')
        ->set('roleFilter', UserRole::MAHASISWA->value)
        ->set('statusFilter', 'nonaktif')
        ->assertSee('Mahasiswa Nonaktif')
        ->assertDontSee($matchingStudent->user_code)
        ->assertDontSee('ADM-LIVE-9');
});

it('limits account table requests to super admins', function (): void {
    $student = User::factory()->create([
        'role' => UserRole::MAHASISWA,
        'must_change_password' => false,
    ]);

    $this->actingAs($student);

    Livewire::test('components.admin.user-table')->assertForbidden();
});

it('rejects an obsolete account session from refreshing the account table', function (): void {
    $admin = User::factory()->create([
        'role' => UserRole::SUPER_ADMIN,
        'must_change_password' => false,
    ]);

    $this->actingAs($admin)->withSession(['auth_version' => $admin->auth_version - 1]);

    Livewire::test('components.admin.user-table')->assertUnauthorized();
});

it('paginates account results in batches of twenty', function (): void {
    $admin = User::factory()->create([
        'role' => UserRole::SUPER_ADMIN,
        'must_change_password' => false,
    ]);

    foreach (range(1, 21) as $number) {
        User::factory()->create([
            'user_code' => sprintf('USR-%02d', $number),
            'name' => sprintf('Pengguna %02d', $number),
            'role' => UserRole::MAHASISWA,
        ]);
    }

    $this->actingAs($admin)->withSession(['auth_version' => $admin->auth_version]);

    Livewire::test('components.admin.user-table')
        ->assertSee('USR-01')
        ->assertDontSee('USR-21')
        ->call('nextPage')
        ->assertSee('USR-21');
});
