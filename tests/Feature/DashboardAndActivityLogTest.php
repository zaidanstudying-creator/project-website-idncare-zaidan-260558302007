<?php

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Report;
use App\Models\ReportType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config(['app.report_key' => str_repeat('f', 64)]);
});

function dashboardType(string $code): ReportType
{
    return ReportType::query()->create(['code' => $code, 'name' => ucfirst($code)]);
}

function dashboardReport(ReportType $type, Category $category, User $reporter, array $overrides = []): Report
{
    return Report::query()->create(array_merge([
        'ticket_no' => 'IDN-DASH-'.strtoupper(substr($type->code, 0, 3)).'-'.random_int(1000, 9999),
        'reporter_id' => $reporter->id,
        'report_type_id' => $type->id,
        'category_id' => $category->id,
        'title' => 'Ringkasan dashboard '.$type->code,
        'description' => 'Isi laporan uji.',
        'assigned_unit' => $type->assignedUnit(),
    ], $overrides));
}

it('shows actual role-scoped dashboard counts and refuses other role dashboards', function (): void {
    $student = User::factory()->create(['must_change_password' => false]);
    $other = User::factory()->create(['must_change_password' => false]);
    $facility = dashboardType(ReportType::FACILITAS);
    $category = Category::query()->create(['report_type_id' => $facility->id, 'name' => 'Lampu', 'is_active' => true]);
    dashboardReport($facility, $category, $student, ['status' => Report::STATUS_DIPROSES]);
    dashboardReport($facility, $category, $other);

    $this->actingAs($student)->get(route('dashboard.mahasiswa'))
        ->assertOk()->assertSee('Laporan saya')->assertSee('Laporan terbaru');
    $this->get(route('dashboard.admin-sarpras'))->assertForbidden();
});

it('limits unit activity logs and log access to authorized admin roles', function (): void {
    $student = User::factory()->create(['must_change_password' => false]);
    $sarpras = User::factory()->create(['role' => UserRole::ADMIN_SARPRAS, 'must_change_password' => false]);
    $kemah = User::factory()->create(['role' => UserRole::ADMIN_KEMAHASISWAAN, 'must_change_password' => false]);
    $super = User::factory()->create(['role' => UserRole::SUPER_ADMIN, 'must_change_password' => false]);
    $facility = dashboardType(ReportType::FACILITAS);
    $aspiration = dashboardType(ReportType::ASPIRASI);
    $facCategory = Category::query()->create(['report_type_id' => $facility->id, 'name' => 'Fasilitas', 'is_active' => true]);
    $aspCategory = Category::query()->create(['report_type_id' => $aspiration->id, 'name' => 'Akademik', 'is_active' => true]);
    $facReport = dashboardReport($facility, $facCategory, $student);
    $aspReport = dashboardReport($aspiration, $aspCategory, $student);
    ActivityLog::query()->create(['user_id' => $sarpras->id, 'role' => 'admin_sarpras', 'action' => 'facility_event', 'subject_type' => Report::class, 'subject_id' => $facReport->id, 'created_at' => now()]);
    ActivityLog::query()->create(['user_id' => null, 'role' => 'mahasiswa', 'action' => 'anonymous_event', 'subject_type' => Report::class, 'subject_id' => $aspReport->id, 'ip_address' => null, 'created_at' => now()]);

    $this->get(route('admin.logs.index'))->assertRedirect(route('login'));
    $this->actingAs($student)->get(route('admin.logs.index'))->assertForbidden();
    $this->actingAs($sarpras)->get(route('admin.logs.index'))->assertOk()->assertSee('Facility event')->assertDontSee('Anonymous event');
    $this->actingAs($kemah)->get(route('admin.logs.index'))->assertOk()->assertSee('Anonymous event')->assertSee('Anonim / tidak ditautkan');
    $this->actingAs($super)->get(route('admin.logs.index'))->assertOk()->assertSee('Facility event')->assertSee('Anonymous event');
});

it('records and displays the time when an activity log is created without an explicit timestamp', function (): void {
    $super = User::factory()->create([
        'role' => UserRole::SUPER_ADMIN,
        'must_change_password' => false,
    ]);

    $this->travelTo('2026-11-27 12:34:00');
    $log = ActivityLog::query()->create([
        'user_id' => $super->id,
        'role' => UserRole::SUPER_ADMIN->value,
        'action' => 'user_updated',
    ]);

    expect($log->fresh()->created_at?->format('Y-m-d H:i:s'))->toBe('2026-11-27 12:34:00');

    $this->actingAs($super)
        ->get(route('admin.logs.index'))
        ->assertOk()
        ->assertSee('27 Nov 2026')
        ->assertSee('12:34 WIB');
});

it('renders role dashboard charts and does not render activity metadata', function (): void {
    $super = User::factory()->create(['role' => UserRole::SUPER_ADMIN, 'must_change_password' => false]);
    ActivityLog::query()->create([
        'user_id' => null,
        'role' => 'mahasiswa',
        'action' => 'report_confirmed',
        'subject_type' => Report::class,
        'subject_id' => 42,
        'new_values' => ['reporter_token' => 'must-not-render'],
        'ip_address' => null,
        'created_at' => now(),
    ]);

    $this->actingAs($super)->get(route('dashboard.super-admin'))
        ->assertOk()->assertSee('Laporan per bulan')->assertSee('Activity log terbaru');
    $this->get(route('admin.logs.index'))
        ->assertOk()->assertSee('Anonim / tidak ditautkan')->assertDontSee('must-not-render')->assertDontSee('reporter_token');
});

it('renders the status chart canvas for every admin dashboard', function (UserRole $role, string $route): void {
    $admin = User::factory()->create(['role' => $role, 'must_change_password' => false]);

    $this->actingAs($admin)
        ->withSession(['auth_version' => $admin->auth_version])
        ->get(route($route))
        ->assertOk()
        ->assertSee('id="status-doughnut-chart"', false)
        ->assertDontSee("@include('livewire.pages.dashboard.partials.status-chart')", false);
})->with([
    [UserRole::ADMIN_SARPRAS, 'dashboard.admin-sarpras'],
    [UserRole::ADMIN_KEMAHASISWAAN, 'dashboard.admin-kemahasiswaan'],
    [UserRole::SUPER_ADMIN, 'dashboard.super-admin'],
]);
