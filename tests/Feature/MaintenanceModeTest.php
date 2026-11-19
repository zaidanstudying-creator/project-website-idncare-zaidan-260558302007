<?php

use App\Enums\UserRole;
use App\Livewire\Pages\Admin\Settings\Index as AdminSettingsIndex;
use App\Livewire\Pages\Auth\Login;
use App\Models\ActivityLog;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('shows the maintenance page to visitors while keeping login available', function (): void {
    SiteSetting::query()->whereKey(1)->update(['maintenance_enabled' => true]);

    $this->get('/')->assertStatus(503)->assertSee('Waduh, maaf ya!');
    $this->get(route('login'))->assertOk();
});

it('sends signed-in admins directly to their dashboards during maintenance', function (): void {
    SiteSetting::query()->whereKey(1)->update(['maintenance_enabled' => true]);

    foreach ([
        [UserRole::ADMIN_SARPRAS, 'dashboard.admin-sarpras'],
        [UserRole::ADMIN_KEMAHASISWAAN, 'dashboard.admin-kemahasiswaan'],
        [UserRole::SUPER_ADMIN, 'dashboard.super-admin'],
    ] as [$role, $dashboardRoute]) {
        $admin = User::factory()->create([
            'role' => $role,
            'must_change_password' => false,
        ]);

        $this->actingAs($admin)
            ->withSession(['auth_version' => $admin->auth_version])
            ->get('/')
            ->assertRedirect(route($dashboardRoute));

        $this->get(route($dashboardRoute))->assertOk()->assertDontSee('Waduh, maaf ya!');
    }
});

it('lets admin routes reach Laravel authorization before showing maintenance', function (): void {
    SiteSetting::query()->whereKey(1)->update(['maintenance_enabled' => true]);

    $this->get(route('dashboard.super-admin'))->assertRedirect(route('login'));
    $this->get(route('admin.settings.index'))->assertRedirect(route('login'));
    $this->get(route('password.change'))->assertRedirect(route('login'));
});

it('sends a super admin to the role dashboard instead of an old intended page during maintenance', function (): void {
    SiteSetting::query()->whereKey(1)->update(['maintenance_enabled' => true]);
    $superAdmin = User::factory()->create([
        'role' => UserRole::SUPER_ADMIN,
        'must_change_password' => false,
    ]);

    $this->actingAs($superAdmin)
        ->withSession([
            'auth_version' => $superAdmin->auth_version,
            'url.intended' => '/',
        ])
        ->get(route('dashboard'))
        ->assertRedirect(route('dashboard.super-admin'));
});

it('keeps a super admin authenticated from Livewire login through the dashboard during maintenance', function (): void {
    SiteSetting::query()->whereKey(1)->update(['maintenance_enabled' => true]);
    $superAdmin = User::factory()->create([
        'role' => UserRole::SUPER_ADMIN,
        'user_code' => 'SUPER-MAINTENANCE-LOGIN',
        'password' => 'temporary-password',
        'must_change_password' => false,
    ]);

    Livewire::test(Login::class)
        ->set('userCode', $superAdmin->user_code)
        ->set('password', 'temporary-password')
        ->call('authenticate')
        ->assertRedirect(route('dashboard'));

    $this->get(route('dashboard'))->assertRedirect(route('dashboard.super-admin'));
    $this->get(route('dashboard.super-admin'))->assertOk()->assertDontSee('Waduh, maaf ya!');
});

it('rejects student login with a maintenance notification while maintenance is active', function (): void {
    SiteSetting::query()->whereKey(1)->update(['maintenance_enabled' => true]);
    $student = User::factory()->create([
        'role' => UserRole::MAHASISWA,
        'user_code' => 'STUDENT-MAINTENANCE-LOGIN',
        'password' => 'temporary-password',
        'must_change_password' => false,
    ]);

    Livewire::test(Login::class)
        ->set('userCode', $student->user_code)
        ->set('password', 'temporary-password')
        ->call('authenticate')
        ->assertRedirect(route('login'));

    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Login sementara tidak tersedia karena website sedang maintenance. Silakan coba lagi nanti.');

    $this->assertGuest();
});

it('lets an active super admin toggle maintenance on and off through Livewire', function (): void {
    $superAdmin = User::factory()->create([
        'role' => UserRole::SUPER_ADMIN,
        'must_change_password' => false,
    ]);

    $this->actingAs($superAdmin)
        ->withSession(['auth_version' => $superAdmin->auth_version])
        ->get(route('admin.settings.index'))
        ->assertOk()
        ->assertSee('Mode maintenance')
        ->assertSee('Aktifkan maintenance');

    Livewire::actingAs($superAdmin)
        ->test(AdminSettingsIndex::class)
        ->call('toggleMaintenance')
        ->assertHasNoErrors()
        ->assertSet('maintenanceEnabled', true)
        ->assertDispatched('app-notification');

    $this->assertDatabaseHas('site_settings', ['id' => 1, 'maintenance_enabled' => true]);
    $this->assertDatabaseHas('activity_logs', ['action' => 'maintenance_mode_updated', 'user_id' => $superAdmin->id]);
    $this->assertDatabaseHas('site_settings', ['id' => 1, 'maintenance_updated_by' => $superAdmin->id]);

    Livewire::actingAs($superAdmin)
        ->test(AdminSettingsIndex::class)
        ->set('maintenanceTitle', '<script>alert(1)</script>')
        ->set('maintenanceMessage', 'Pesan pratinjau')
        ->call('toggleMaintenancePreview')
        ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
        ->assertSee('Pesan pratinjau');

    $this->app['auth']->logout();
    $this->get('/')->assertStatus(503)->assertSee('Waduh, maaf ya!');

    Livewire::actingAs($superAdmin)
        ->test(AdminSettingsIndex::class)
        ->call('toggleMaintenance')
        ->assertHasNoErrors()
        ->assertSet('maintenanceEnabled', false)
        ->assertDispatched('app-notification');

    $this->assertDatabaseHas('site_settings', ['id' => 1, 'maintenance_enabled' => false]);
    expect(ActivityLog::query()->where('action', 'maintenance_mode_updated')->where('user_id', $superAdmin->id)->count())->toBe(2);
});

it('saves portal preferences and renders the customized maintenance message', function (): void {
    $superAdmin = User::factory()->create([
        'role' => UserRole::SUPER_ADMIN,
        'must_change_password' => false,
    ]);

    Livewire::actingAs($superAdmin)
        ->test(AdminSettingsIndex::class)
        ->set('campusName', 'Kampus Demo')
        ->set('tagline', 'Tagline Baru')
        ->set('supportContact', 'admin@example.test')
        ->set('announcementText', 'Jadwal layanan berubah')
        ->set('announcementEnabled', true)
        ->set('fontScale', 110)
        ->set('maintenanceTitle', 'Kami sedang memperbarui layanan')
        ->set('maintenanceMessage', 'Silakan kembali sebentar lagi.')
        ->call('saveSettings')
        ->assertHasNoErrors()
        ->assertDispatched('app-notification');

    $this->assertDatabaseHas('site_settings', [
        'id' => 1,
        'campus_name' => 'Kampus Demo',
        'tagline' => 'Tagline Baru',
        'support_contact' => 'admin@example.test',
        'announcement_enabled' => true,
        'font_scale' => 110,
        'maintenance_title' => 'Kami sedang memperbarui layanan',
    ]);

    SiteSetting::query()->whereKey(1)->update(['maintenance_enabled' => true]);
    $this->app['auth']->logout();
    $this->get('/')->assertStatus(503)->assertSee('Kami sedang memperbarui layanan')->assertSee('Silakan kembali sebentar lagi.');
});

it('lets the super admin control report types individually or all at once', function (): void {
    $superAdmin = User::factory()->create([
        'role' => UserRole::SUPER_ADMIN,
        'must_change_password' => false,
    ]);

    Livewire::actingAs($superAdmin)
        ->test(AdminSettingsIndex::class)
        ->set('aspirationReportsEnabled', false)
        ->call('saveSettings')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('site_settings', [
        'id' => 1,
        'facility_reports_enabled' => true,
        'aspiration_reports_enabled' => false,
        'violation_reports_enabled' => true,
    ]);

    $student = User::factory()->create([
        'role' => UserRole::MAHASISWA,
        'must_change_password' => false,
    ]);

    $this->actingAs($student)
        ->withSession(['auth_version' => $student->auth_version])
        ->get(route('reports.create.form', 'aspirasi'))
        ->assertNotFound();

    Livewire::actingAs($superAdmin)
        ->test(AdminSettingsIndex::class)
        ->call('setAllReportTypesEnabled', false)
        ->call('saveSettings')
        ->assertHasNoErrors()
        ->assertSet('facilityReportsEnabled', false)
        ->assertSet('aspirationReportsEnabled', false)
        ->assertSet('violationReportsEnabled', false);

    $this->actingAs($student)
        ->withSession(['auth_version' => $student->auth_version])
        ->get(route('reports.create'))
        ->assertOk()
        ->assertSee('semua jenis pelaporan sedang dinonaktifkan');
});

it('shows maintenance to regular accounts and forbids them from changing the setting', function (): void {
    SiteSetting::query()->whereKey(1)->update(['maintenance_enabled' => true]);
    $student = User::factory()->create([
        'role' => UserRole::MAHASISWA,
        'must_change_password' => false,
    ]);

    $this->actingAs($student)
        ->withSession(['auth_version' => $student->auth_version])
        ->get(route('dashboard.mahasiswa'))
        ->assertStatus(503)
        ->assertSee('Waduh, maaf ya!');

    SiteSetting::query()->whereKey(1)->update(['maintenance_enabled' => false]);

    $this->get(route('admin.settings.index'))->assertForbidden();
    expect(ActivityLog::query()->where('action', 'maintenance_mode_updated')->exists())->toBeFalse();
});
