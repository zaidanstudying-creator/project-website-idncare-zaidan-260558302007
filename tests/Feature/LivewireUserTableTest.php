<?php

use App\Enums\UserRole;
use App\Livewire\Pages\Admin\Users\Index as AdminUsersIndex;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Report;
use App\Models\ReportAttachment;
use App\Models\ReportStatusHistory;
use App\Models\ReportType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
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
        ->assertSee('Edit informasi akun')
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

it('renders the correct student fields each time the account role changes', function (): void {
    $admin = User::factory()->create([
        'role' => UserRole::SUPER_ADMIN,
        'must_change_password' => false,
    ]);

    $this->actingAs($admin)->withSession(['auth_version' => $admin->auth_version]);

    $component = Livewire::test(AdminUsersIndex::class);
    $component->assertSeeHtml('wire:ignore.self');

    expect((bool) preg_match('/<fieldset[^>]*data-student-fields[^>]*\shidden(?:\s|>)/', $component->html()))->toBeFalse();

    $component->set('role', UserRole::ADMIN_SARPRAS->value)
        ->assertSet('role', UserRole::ADMIN_SARPRAS->value)
        ->assertSeeHtml('wire:ignore.self');

    expect((bool) preg_match('/<fieldset[^>]*data-student-fields[^>]*\shidden(?:\s|>)/', $component->html()))->toBeTrue();

    $component->set('role', UserRole::MAHASISWA->value)
        ->assertSet('role', UserRole::MAHASISWA->value)
        ->assertSeeHtml('wire:ignore.self');

    expect((bool) preg_match('/<fieldset[^>]*data-student-fields[^>]*\shidden(?:\s|>)/', $component->html()))->toBeFalse();
});

it('clears temporary credentials when the success dialog is dismissed', function (): void {
    $admin = User::factory()->create([
        'role' => UserRole::SUPER_ADMIN,
        'must_change_password' => false,
    ]);

    $this->actingAs($admin)->withSession(['auth_version' => $admin->auth_version]);

    Livewire::test(AdminUsersIndex::class)
        ->call('showCredentials', 'NEW-STUDENT-01', 'one-time-password')
        ->assertSeeHtml('wire:click="dismissCredentials"')
        ->assertSee('one-time-password')
        ->call('dismissCredentials')
        ->assertSet('temporaryPassword', null)
        ->assertDontSee('one-time-password');
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

it('permanently deletes an account that has no retained report history', function (): void {
    $admin = User::factory()->create([
        'role' => UserRole::SUPER_ADMIN,
        'must_change_password' => false,
    ]);
    $target = User::factory()->create([
        'user_code' => 'MHS-REMOVE-01',
        'role' => UserRole::MAHASISWA,
        'must_change_password' => false,
    ]);
    $notification = $target->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => 'test-notification',
        'data' => ['message' => 'Notifikasi uji'],
    ]);

    Livewire::actingAs($admin)->test('components.admin.user-table')
        ->call('deleteUser', $target->id)
        ->assertDispatched('app-notification', message: 'Akun berhasil dihapus permanen. Semua laporan dan riwayatnya tetap tersimpan.', type: 'success');

    expect(User::query()->whereKey($target->id)->exists())->toBeFalse()
        ->and($notification->fresh())->toBeNull();
    $this->assertDatabaseHas('activity_logs', [
        'user_id' => $admin->id,
        'action' => 'user_deleted',
        'subject_id' => $target->id,
    ]);
});

it('deletes an account while keeping its reports and immutable activity history', function (): void {
    $admin = User::factory()->create([
        'role' => UserRole::SUPER_ADMIN,
        'must_change_password' => false,
    ]);
    $target = User::factory()->create([
        'user_code' => 'MHS-REMOVE-REPORTS',
        'role' => UserRole::MAHASISWA,
        'must_change_password' => false,
    ]);
    $reportType = ReportType::query()->create([
        'code' => ReportType::FACILITAS,
        'name' => 'Laporan Fasilitas',
    ]);
    $category = Category::query()->create([
        'report_type_id' => $reportType->id,
        'name' => 'Kebersihan',
        'is_active' => true,
    ]);
    $report = Report::query()->create([
        'ticket_no' => 'IDN-DELETE-ACCOUNT-01',
        'reporter_id' => $target->id,
        'report_type_id' => $reportType->id,
        'category_id' => $category->id,
        'title' => 'Lampu koridor rusak',
        'description' => 'Lampu koridor lantai dua mati.',
        'status' => Report::STATUS_DIKIRIM,
        'assigned_unit' => ReportType::UNIT_SARPRAS,
    ]);
    $history = ReportStatusHistory::query()->create([
        'report_id' => $report->id,
        'to_status' => Report::STATUS_DIKIRIM,
        'note' => 'Laporan diterima.',
        'changed_by' => $target->id,
        'created_at' => now(),
    ]);
    $attachment = ReportAttachment::query()->create([
        'report_id' => $report->id,
        'file_path' => 'reports/IDN-DELETE-ACCOUNT-01/before/photo.jpg',
        'kind' => 'before',
        'uploaded_by' => $target->id,
        'created_at' => now(),
    ]);
    $oldActivity = ActivityLog::query()->create([
        'user_id' => $target->id,
        'role' => UserRole::MAHASISWA->value,
        'action' => 'login',
        'created_at' => now(),
    ]);

    Livewire::actingAs($admin)->test('components.admin.user-table')
        ->call('deleteUser', $target->id)
        ->assertDispatched('app-notification', message: 'Akun berhasil dihapus permanen. Semua laporan dan riwayatnya tetap tersimpan.', type: 'success');

    expect(User::query()->whereKey($target->id)->exists())->toBeFalse()
        ->and(ActivityLog::query()->find($oldActivity->id)?->user)->toBeNull();

    $this->assertDatabaseHas('reports', [
        'id' => $report->id,
        'ticket_no' => 'IDN-DELETE-ACCOUNT-01',
        'reporter_id' => null,
        'title' => 'Lampu koridor rusak',
    ]);
    $this->assertDatabaseHas('report_status_histories', [
        'id' => $history->id,
        'report_id' => $report->id,
        'changed_by' => null,
        'note' => 'Laporan diterima.',
    ]);
    $this->assertDatabaseHas('report_attachments', [
        'id' => $attachment->id,
        'report_id' => $report->id,
        'file_path' => 'reports/IDN-DELETE-ACCOUNT-01/before/photo.jpg',
        'uploaded_by' => null,
    ]);
    $this->assertDatabaseHas('activity_logs', [
        'id' => $oldActivity->id,
        'user_id' => $target->id,
        'action' => 'login',
    ]);
});
