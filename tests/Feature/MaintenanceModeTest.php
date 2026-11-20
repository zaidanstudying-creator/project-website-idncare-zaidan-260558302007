<?php

use App\Enums\UserRole;
use App\Http\Middleware\MaintenancePage;
use App\Livewire\Pages\Admin\Settings\Index as AdminSettingsIndex;
use App\Livewire\Pages\Auth\Login;
use App\Models\ActivityLog;
use App\Models\ReportType;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('shows the maintenance page to visitors while keeping login available', function (): void {
    SiteSetting::query()->whereKey(1)->update(['maintenance_enabled' => true]);

    $this->get('/')->assertStatus(503)->assertSee('Waduh, maaf ya!');
    $this->get(route('login'))->assertOk();
});

it('allows guest Livewire login updates through the maintenance middleware', function (): void {
    SiteSetting::query()->whereKey(1)->update(['maintenance_enabled' => true]);
    $livewireUpdateRoute = Route::getRoutes()->getByName('default-livewire.update');

    expect($livewireUpdateRoute)->not->toBeNull();

    $request = Request::create('/'.ltrim($livewireUpdateRoute->uri(), '/'), 'POST');
    $request->setRouteResolver(fn () => $livewireUpdateRoute);
    $response = app(MaintenancePage::class)->handle($request, fn () => response('Livewire request reached its endpoint.'));

    expect($response->getContent())->toBe('Livewire request reached its endpoint.');
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
        ->assertRedirect(route('dashboard.super-admin'));

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
        ->set('maintenanceComingSoon', 'SEGERA HADIR')
        ->call('toggleMaintenancePreview')
        ->assertSee('Pratinjau halaman maintenance IDN CARE')
        ->assertSee('sandbox="allow-scripts"', false)
        ->assertSee('Pesan pratinjau')
        ->assertSee('SEGERA HADIR')
        ->assertSee('construction-title')
        ->assertSee('&amp;lt;script&amp;gt;alert(1)&amp;lt;/script&amp;gt;', false);

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
        ->set('announcementSpeed', 55)
        ->set('fontScale', 110)
        ->set('maintenanceTitle', 'Kami sedang memperbarui layanan')
        ->set('maintenanceMessage', 'Silakan kembali sebentar lagi.')
        ->set('maintenanceComingSoon', 'SEGERA HADIR')
        ->call('saveSettings')
        ->assertHasNoErrors()
        ->assertDispatched('app-notification');

    $this->assertDatabaseHas('site_settings', [
        'id' => 1,
        'campus_name' => 'Kampus Demo',
        'tagline' => 'Tagline Baru',
        'support_contact' => 'admin@example.test',
        'announcement_enabled' => true,
        'announcement_speed' => 55,
        'font_scale' => 110,
        'maintenance_title' => 'Kami sedang memperbarui layanan',
        'maintenance_coming_soon' => 'SEGERA HADIR',
    ]);

    SiteSetting::query()->whereKey(1)->update(['maintenance_enabled' => true]);
    $this->app['auth']->logout();
    $this->get('/')->assertStatus(503)->assertSee('Kami sedang memperbarui layanan')->assertSee('Silakan kembali sebentar lagi.')->assertSee('SEGERA HADIR');
});

it('renders enabled announcements as a dismissible running text at the saved speed', function (): void {
    SiteSetting::query()->whereKey(1)->update([
        'announcement_text' => 'Jadwal perbaikan perpustakaan',
        'announcement_enabled' => true,
        'announcement_speed' => 50,
    ]);
    $student = User::factory()->create([
        'role' => UserRole::MAHASISWA,
        'must_change_password' => false,
    ]);

    $this->actingAs($student)
        ->withSession(['auth_version' => $student->auth_version])
        ->get(route('dashboard.mahasiswa'))
        ->assertOk()
        ->assertSee('data-announcement-marquee', false)
        ->assertSee('style="--announcement-duration: 50s"', false)
        ->assertSee('aria-label="Tutup pengumuman"', false)
        ->assertSee('Jadwal perbaikan perpustakaan', false);
});

it('saves the maintenance editor without requiring unrelated portal fields to validate', function (): void {
    $superAdmin = User::factory()->create([
        'role' => UserRole::SUPER_ADMIN,
        'must_change_password' => false,
    ]);

    Livewire::actingAs($superAdmin)
        ->test(AdminSettingsIndex::class)
        ->set('campusName', '')
        ->set('maintenanceTitle', 'Pembaruan sistem berlangsung')
        ->set('maintenanceMessage', 'Layanan akan kembali setelah pemeliharaan selesai.')
        ->set('maintenanceComingSoon', 'SEGERA KEMBALI')
        ->call('saveMaintenanceMessages')
        ->assertHasNoErrors()
        ->assertDispatched('app-notification');

    $this->assertDatabaseHas('site_settings', [
        'id' => 1,
        'campus_name' => 'IDN CARE',
        'maintenance_title' => 'Pembaruan sistem berlangsung',
        'maintenance_message' => 'Layanan akan kembali setelah pemeliharaan selesai.',
        'maintenance_coming_soon' => 'SEGERA KEMBALI',
        'maintenance_updated_by' => $superAdmin->id,
    ]);

    SiteSetting::query()->whereKey(1)->update(['maintenance_enabled' => true]);
    $this->app['auth']->logout();

    $this->get('/')
        ->assertStatus(503)
        ->assertSee('Pembaruan sistem berlangsung')
        ->assertSee('Layanan akan kembali setelah pemeliharaan selesai.')
        ->assertSee('SEGERA KEMBALI');
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
        ->get(route('dashboard.mahasiswa'))
        ->assertOk()
        ->assertSee('href="'.route('reports.create').'" class="portal-nav-link"', false);

    $this->get(route('reports.create'))
        ->assertOk()
        ->assertSee('semua jenis pelaporan sedang dinonaktifkan');
});

it('saves report type choices independently from the other portal settings', function (): void {
    $superAdmin = User::factory()->create([
        'role' => UserRole::SUPER_ADMIN,
        'must_change_password' => false,
    ]);
    SiteSetting::query()->whereKey(1)->update(['campus_name' => 'Nama kampus tetap']);

    Livewire::actingAs($superAdmin)
        ->test(AdminSettingsIndex::class)
        ->set('facilityReportsEnabled', false)
        ->set('aspirationReportsEnabled', true)
        ->set('violationReportsEnabled', false)
        ->call('saveReportAvailability')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('site_settings', [
        'id' => 1,
        'campus_name' => 'Nama kampus tetap',
        'facility_reports_enabled' => false,
        'aspiration_reports_enabled' => true,
        'violation_reports_enabled' => false,
    ]);
});

it('opens every enabled report form to students after the super admin saves the choices', function (): void {
    $student = User::factory()->create([
        'role' => UserRole::MAHASISWA,
        'must_change_password' => false,
    ]);
    $this->actingAs($student)->withSession(['auth_version' => $student->auth_version]);
    $this->get(route('reports.create'))
        ->assertOk()
        ->assertSee('semua jenis pelaporan sedang dinonaktifkan')
        ->assertDontSee('href="'.route('reports.create.form', 'fasilitas').'"', false);

    SiteSetting::query()->whereKey(1)->update([
        'facility_reports_enabled' => false,
        'aspiration_reports_enabled' => false,
        'violation_reports_enabled' => false,
    ]);

    $superAdmin = User::factory()->create([
        'role' => UserRole::SUPER_ADMIN,
        'must_change_password' => false,
    ]);

    Livewire::actingAs($superAdmin)
        ->test(AdminSettingsIndex::class)
        ->call('setAllReportTypesEnabled', true)
        ->call('saveReportAvailability')
        ->assertHasNoErrors()
        ->assertDispatched('app-notification', message: 'Pilihan jenis laporan berhasil disimpan.', type: 'success');

    $this->actingAs($student)->withSession(['auth_version' => $student->auth_version]);

    foreach ([ReportType::FACILITAS, ReportType::ASPIRASI, ReportType::PELANGGARAN] as $code) {
        $this->assertDatabaseHas('report_types', ['code' => $code]);
        $this->get(route('reports.create', ['type' => $code]))
            ->assertOk()
            ->assertSee(route('reports.create.form', $code));

        $this->get(route('reports.create.form', $code))->assertOk();
    }
});

it('keeps dashboard, sidebar, selector, and form choices aligned with saved availability', function (): void {
    $superAdmin = User::factory()->create(['role' => UserRole::SUPER_ADMIN, 'must_change_password' => false]);

    Livewire::actingAs($superAdmin)
        ->test(AdminSettingsIndex::class)
        ->set('facilityReportsEnabled', false)
        ->set('aspirationReportsEnabled', true)
        ->set('violationReportsEnabled', false)
        ->call('saveReportAvailability')
        ->assertHasNoErrors();

    $student = User::factory()->create(['role' => UserRole::MAHASISWA, 'must_change_password' => false]);
    $this->actingAs($student)->withSession(['auth_version' => $student->auth_version]);

    $this->get(route('dashboard.mahasiswa'))
        ->assertOk()
        ->assertSee('href="'.route('reports.create').'" class="portal-nav-link"', false)
        ->assertSee('href="'.route('reports.create.form', 'aspirasi').'"', false)
        ->assertDontSee('href="'.route('reports.create.form', 'fasilitas').'"', false)
        ->assertDontSee('href="'.route('reports.create.form', 'pelanggaran').'"', false);

    $this->get(route('reports.create'))
        ->assertOk()
        ->assertSee('href="'.route('reports.create.form', 'aspirasi').'"', false)
        ->assertDontSee('href="'.route('reports.create.form', 'fasilitas').'"', false)
        ->assertDontSee('href="'.route('reports.create.form', 'pelanggaran').'"', false);

    $this->get(route('reports.create.form', 'aspirasi'))
        ->assertOk()
        ->assertDontSee('href="'.route('reports.create.form', 'fasilitas').'"', false)
        ->assertDontSee('href="'.route('reports.create.form', 'pelanggaran').'"', false);

    $this->get(route('reports.create.form', 'fasilitas'))->assertNotFound();
    $this->get(route('reports.create.form', 'pelanggaran'))->assertNotFound();
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
