<?php

use App\Enums\UserRole;
use App\Livewire\Pages\Reports\Show as StudentReportShow;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Location;
use App\Models\Report;
use App\Models\ReportAttachment;
use App\Models\ReportStatusHistory;
use App\Models\ReportType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config(['app.report_key' => str_repeat('v', 64)]);
});

function visibilityType(string $code): ReportType
{
    return ReportType::query()->create(['code' => $code, 'name' => ucfirst($code)]);
}

function visibilityReport(ReportType $type, User $reporter, array $attributes = []): Report
{
    $category = Category::query()->create([
        'report_type_id' => $type->id,
        'name' => 'Kategori '.$type->code,
        'is_active' => true,
    ]);
    $location = Location::query()->create(['name' => 'Gedung Utama', 'is_active' => true]);

    return Report::query()->create([
        'ticket_no' => 'IDN-VIS-'.strtoupper($type->code).'-'.fake()->unique()->numerify('####'),
        'reporter_id' => $reporter->id,
        'report_type_id' => $type->id,
        'category_id' => $category->id,
        'location_id' => $location->id,
        'title' => 'Judul publik '.$type->code,
        'description' => 'Isi laporan.',
        'assigned_unit' => $type->assignedUnit(),
        ...$attributes,
    ]);
}

it('shows only approved public facilities and aspirations on the monitor and masks anonymous names', function (): void {
    $anonymous = User::factory()->create(['name' => 'Nama Anonim Rahasia', 'must_change_password' => false]);
    $named = User::factory()->create(['name' => 'Nama Publik', 'must_change_password' => false]);
    $facilityType = visibilityType(ReportType::FACILITAS);
    $aspirationType = visibilityType(ReportType::ASPIRASI);
    $violationType = visibilityType(ReportType::PELANGGARAN);
    $publicAnonymous = visibilityReport($facilityType, $anonymous, [
        'is_anonymous' => true,
        'requested_visibility' => Report::VISIBILITY_UMUM,
        'visibility_status' => Report::VISIBILITY_APPROVED,
        'approved_visibility' => Report::VISIBILITY_UMUM,
    ]);
    $publicNamed = visibilityReport($aspirationType, $named, [
        'requested_visibility' => Report::VISIBILITY_UMUM,
        'visibility_status' => Report::VISIBILITY_APPROVED,
        'approved_visibility' => Report::VISIBILITY_UMUM,
    ]);
    $secret = visibilityReport($facilityType, $named, [
        'title' => 'Judul laporan rahasia yang tidak tampil',
        'requested_visibility' => Report::VISIBILITY_RAHASIA,
        'visibility_status' => Report::VISIBILITY_PENDING,
    ]);
    $violation = visibilityReport($violationType, $anonymous, [
        'title' => 'Dugaan pelanggaran yang tidak tampil',
        'reporter_id' => null,
        'reporter_token' => Report::reporterTokenFor($anonymous),
        'is_anonymous' => true,
        'requested_visibility' => Report::VISIBILITY_RAHASIA,
        'visibility_status' => Report::VISIBILITY_APPROVED,
        'approved_visibility' => Report::VISIBILITY_RAHASIA,
    ]);
    $student = User::factory()->create(['must_change_password' => false]);

    $response = $this->actingAs($student)->get(route('monitor.index'));
    $response->assertOk()
        ->assertSee('Judul publik fasilitas')
        ->assertSee('Judul publik aspirasi')
        ->assertSee('Nama Publik')
        ->assertDontSee('Nama Anonim Rahasia')
        ->assertDontSee('Judul laporan rahasia yang tidak tampil')
        ->assertDontSee('Dugaan pelanggaran yang tidak tampil');

    $anonymousCard = collect($response->viewData('reports')->items())->firstWhere('id', $publicAnonymous->id);
    expect($anonymousCard->public_reporter_name)->toBeNull()
        ->and(array_key_exists('reporter_id', $anonymousCard->getAttributes()))->toBeFalse()
        ->and(array_key_exists('reporter_token', $anonymousCard->getAttributes()))->toBeFalse()
        ->and(Report::query()->public()->pluck('id')->all())->toContain($publicAnonymous->id, $publicNamed->id)
        ->and(Report::query()->public()->pluck('id')->all())->not->toContain($secret->id, $violation->id);
});

it('requires authentication and limits the monitor route to students', function (): void {
    $this->get(route('monitor.index'))->assertRedirect(route('login'));
    $admin = User::factory()->create(['role' => UserRole::ADMIN_SARPRAS, 'must_change_password' => false]);
    $this->actingAs($admin)->get(route('monitor.index'))->assertForbidden();
});

it('approves or rejects public visibility only by the assigned unit and excludes violations', function (): void {
    $reporter = User::factory()->create(['must_change_password' => false]);
    $facility = visibilityReport(visibilityType(ReportType::FACILITAS), $reporter, [
        'requested_visibility' => Report::VISIBILITY_UMUM,
    ]);
    $aspiration = visibilityReport(visibilityType(ReportType::ASPIRASI), $reporter, [
        'requested_visibility' => Report::VISIBILITY_UMUM,
    ]);
    $violation = visibilityReport(visibilityType(ReportType::PELANGGARAN), $reporter, [
        'requested_visibility' => Report::VISIBILITY_UMUM,
        'visibility_status' => Report::VISIBILITY_PENDING,
    ]);
    $sarpras = User::factory()->create(['role' => UserRole::ADMIN_SARPRAS, 'must_change_password' => false]);
    $kemahasiswaan = User::factory()->create(['role' => UserRole::ADMIN_KEMAHASISWAAN, 'must_change_password' => false]);

    $this->actingAs($sarpras)->post(route('admin.reports.visibility.decide', $facility), ['decision' => 'approve'])
        ->assertRedirect(route('admin.reports.show', $facility));
    $this->actingAs($sarpras)->post(route('admin.reports.visibility.decide', $aspiration), ['decision' => 'approve'])
        ->assertForbidden();
    $this->actingAs($kemahasiswaan)->post(route('admin.reports.visibility.decide', $aspiration), ['decision' => 'reject'])
        ->assertRedirect(route('admin.reports.show', $aspiration));
    $this->actingAs($kemahasiswaan)->post(route('admin.reports.visibility.decide', $violation), ['decision' => 'approve'])
        ->assertForbidden();

    expect($facility->fresh()->visibility_status)->toBe(Report::VISIBILITY_APPROVED)
        ->and($facility->fresh()->approved_visibility)->toBe(Report::VISIBILITY_UMUM)
        ->and($aspiration->fresh()->visibility_status)->toBe(Report::VISIBILITY_REJECTED)
        ->and($aspiration->fresh()->approved_visibility)->toBe(Report::VISIBILITY_RAHASIA)
        ->and($violation->fresh()->approved_visibility)->toBeNull();
});

it('lets a student view only owned reports and token-owned anonymous reports', function (): void {
    $owner = User::factory()->create(['must_change_password' => false]);
    $other = User::factory()->create(['must_change_password' => false]);
    $type = visibilityType(ReportType::PELANGGARAN);
    $owned = visibilityReport($type, $owner, [
        'reporter_id' => null,
        'reporter_token' => Report::reporterTokenFor($owner),
        'is_anonymous' => true,
        'requested_visibility' => Report::VISIBILITY_RAHASIA,
        'visibility_status' => Report::VISIBILITY_APPROVED,
        'approved_visibility' => Report::VISIBILITY_RAHASIA,
    ]);
    $otherReport = visibilityReport($type, $other, [
        'reporter_id' => null,
        'reporter_token' => Report::reporterTokenFor($other),
        'is_anonymous' => true,
        'requested_visibility' => Report::VISIBILITY_RAHASIA,
        'visibility_status' => Report::VISIBILITY_APPROVED,
        'approved_visibility' => Report::VISIBILITY_RAHASIA,
    ]);

    $this->actingAs($owner)->get(route('reports.show', $owned))->assertOk()->assertSee($owned->ticket_no);
    $this->get(route('reports.show', $otherReport))->assertNotFound();
});

it('lets the owner confirm a finished report once', function (): void {
    $reporter = User::factory()->create(['must_change_password' => false]);
    $report = visibilityReport(visibilityType(ReportType::ASPIRASI), $reporter, [
        'status' => Report::STATUS_SELESAI,
        'completed_at' => now()->subDays(2),
    ]);

    $this->actingAs($reporter)->post(route('reports.confirm', $report))
        ->assertRedirect(route('reports.show', $report));

    expect($report->fresh()->confirmed_at)->not->toBeNull();
    $this->post(route('reports.confirm', $report))->assertForbidden();
});

it('reopens token-owned anonymous reports without linking the reporter in history or logs', function (): void {
    $reporter = User::factory()->create(['must_change_password' => false]);
    $type = visibilityType(ReportType::PELANGGARAN);
    $report = visibilityReport($type, $reporter, [
        'reporter_id' => null,
        'reporter_token' => Report::reporterTokenFor($reporter),
        'is_anonymous' => true,
        'status' => Report::STATUS_SELESAI,
        'requested_visibility' => Report::VISIBILITY_RAHASIA,
        'visibility_status' => Report::VISIBILITY_APPROVED,
        'approved_visibility' => Report::VISIBILITY_RAHASIA,
        'completed_at' => now()->subDay(),
    ]);

    $this->actingAs($reporter)->post(route('reports.reopen', $report), [
        'reason' => 'Tindak lanjut belum sesuai.',
    ])->assertRedirect(route('reports.show', $report));

    $history = ReportStatusHistory::query()->where('report_id', $report->id)->sole();
    $log = ActivityLog::query()->where('action', 'report_reopened')->sole();
    expect($report->fresh()->status)->toBe(Report::STATUS_DIPROSES)
        ->and($report->fresh()->reopened_count)->toBe(1)
        ->and($report->fresh()->completed_at)->toBeNull()
        ->and($report->fresh()->confirmed_at)->toBeNull()
        ->and($history->from_status)->toBe(Report::STATUS_SELESAI)
        ->and($history->to_status)->toBe(Report::STATUS_DIPROSES)
        ->and($history->note)->toBe('Tindak lanjut belum sesuai.')
        ->and($history->changed_by)->toBeNull()
        ->and($log->user_id)->toBeNull()
        ->and($log->ip_address)->toBeNull()
        ->and($log->new_values)->toBe(['status' => Report::STATUS_DIPROSES, 'reopened_count' => 1]);
});

it('reopens a finished report from the student Livewire page and refreshes its displayed status', function (): void {
    $reporter = User::factory()->create(['must_change_password' => false]);
    $report = visibilityReport(visibilityType(ReportType::ASPIRASI), $reporter, [
        'status' => Report::STATUS_SELESAI,
        'completed_at' => now()->subDay(),
    ]);

    Livewire::actingAs($reporter)
        ->test(StudentReportShow::class, ['report' => (string) $report->getKey()])
        ->assertSee('Selesai')
        ->set('reopenReason', 'Tindak lanjut belum sesuai.')
        ->call('reopen')
        ->assertHasNoErrors()
        ->assertSee('Diproses');

    expect($report->fresh()->status)->toBe(Report::STATUS_DIPROSES)
        ->and($report->fresh()->completed_at)->toBeNull();
});

it('requires a reopen reason, does not impose the proposed reopen cap, and refuses other owners', function (): void {
    $owner = User::factory()->create(['must_change_password' => false]);
    $other = User::factory()->create(['must_change_password' => false]);
    $report = visibilityReport(visibilityType(ReportType::FACILITAS), $owner, [
        'status' => Report::STATUS_SELESAI,
        'completed_at' => now()->subDay(),
    ]);

    $this->actingAs($owner)->post(route('reports.reopen', $report), ['reason' => 'no'])
        ->assertSessionHasErrors('reason');
    $this->actingAs($other)->post(route('reports.reopen', $report), ['reason' => 'Ada masalah lagi.'])
        ->assertForbidden();

    $report->forceFill(['reopened_count' => 2])->save();
    $this->actingAs($owner)->post(route('reports.reopen', $report), ['reason' => 'Masih belum sesuai.'])
        ->assertRedirect(route('reports.show', $report));
    expect($report->fresh()->status)->toBe(Report::STATUS_DIPROSES)
        ->and($report->fresh()->reopened_count)->toBe(3)
        ->and(ReportStatusHistory::query()->where('report_id', $report->id)->count())->toBe(1);
});

it('confirms token-owned anonymous violations without adding identity metadata to history or logs', function (): void {
    $reporter = User::factory()->create(['must_change_password' => false]);
    $report = visibilityReport(visibilityType(ReportType::PELANGGARAN), $reporter, [
        'reporter_id' => null,
        'reporter_token' => Report::reporterTokenFor($reporter),
        'is_anonymous' => true,
        'status' => Report::STATUS_SELESAI,
        'requested_visibility' => Report::VISIBILITY_RAHASIA,
        'visibility_status' => Report::VISIBILITY_APPROVED,
        'approved_visibility' => Report::VISIBILITY_RAHASIA,
        'completed_at' => now()->subDay(),
    ]);

    $this->actingAs($reporter)->post(route('reports.confirm', $report))->assertRedirect(route('reports.show', $report));

    $log = ActivityLog::query()->sole();
    expect($report->fresh()->confirmed_at)->not->toBeNull()
        ->and(ReportStatusHistory::query()->where('report_id', $report->id)->count())->toBe(0)
        ->and($log->user_id)->toBeNull()
        ->and($log->ip_address)->toBeNull()
        ->and($log->subject_id)->toBe($report->id)
        ->and($log->old_values)->toBe(['confirmed_at' => null]);
});

it('serves a public report attachment only to users allowed to view that report', function (): void {
    Storage::fake('local');
    $owner = User::factory()->create(['must_change_password' => false]);
    $other = User::factory()->create(['must_change_password' => false]);
    $report = visibilityReport(visibilityType(ReportType::ASPIRASI), $owner, [
        'requested_visibility' => Report::VISIBILITY_UMUM,
        'visibility_status' => Report::VISIBILITY_APPROVED,
        'approved_visibility' => Report::VISIBILITY_UMUM,
    ]);
    Storage::disk('local')->put('reports/public-evidence.png', 'image');
    $attachment = ReportAttachment::query()->create([
        'report_id' => $report->id,
        'file_path' => 'reports/public-evidence.png',
        'kind' => 'evidence',
    ]);

    $this->actingAs($other)->get(route('attachments.show', $attachment))->assertOk();
});

it('auto-confirms only completed reports with no response after seven days', function (): void {
    $reporter = User::factory()->create(['must_change_password' => false]);
    $type = visibilityType(ReportType::FACILITAS);
    $old = visibilityReport($type, $reporter, [
        'status' => Report::STATUS_SELESAI,
        'completed_at' => now()->subDays(7)->subMinute(),
    ]);
    $recent = visibilityReport($type, $reporter, [
        'status' => Report::STATUS_SELESAI,
        'completed_at' => now()->subDays(6),
    ]);
    $alreadyConfirmed = visibilityReport($type, $reporter, [
        'status' => Report::STATUS_SELESAI,
        'completed_at' => now()->subDays(9),
        'confirmed_at' => now()->subDays(2),
    ]);
    $inProgress = visibilityReport($type, $reporter, [
        'status' => Report::STATUS_DIPROSES,
        'completed_at' => now()->subDays(9),
    ]);
    $reopened = visibilityReport($type, $reporter, [
        'status' => Report::STATUS_DIPROSES,
        'completed_at' => now()->subDays(10),
        'reopened_count' => 1,
    ]);

    $this->artisan('reports:auto-confirm')->expectsOutput('1 laporan dikonfirmasi otomatis.')->assertSuccessful();

    expect($old->fresh()->confirmed_at)->not->toBeNull()
        ->and($recent->fresh()->confirmed_at)->toBeNull()
        ->and($alreadyConfirmed->fresh()->confirmed_at->toDateTimeString())->toBe($alreadyConfirmed->confirmed_at->toDateTimeString())
        ->and($inProgress->fresh()->confirmed_at)->toBeNull()
        ->and($reopened->fresh()->confirmed_at)->toBeNull();
});

it('auto-confirms an anonymous violation without linking the reporter in its activity log', function (): void {
    $reporter = User::factory()->create(['must_change_password' => false]);
    $report = visibilityReport(visibilityType(ReportType::PELANGGARAN), $reporter, [
        'reporter_id' => null,
        'reporter_token' => Report::reporterTokenFor($reporter),
        'is_anonymous' => true,
        'status' => Report::STATUS_SELESAI,
        'requested_visibility' => Report::VISIBILITY_RAHASIA,
        'visibility_status' => Report::VISIBILITY_APPROVED,
        'approved_visibility' => Report::VISIBILITY_RAHASIA,
        'completed_at' => now()->subDays(8),
    ]);

    $this->artisan('reports:auto-confirm')->expectsOutput('1 laporan dikonfirmasi otomatis.')->assertSuccessful();

    $log = ActivityLog::query()->sole();
    expect($report->fresh()->confirmed_at)->not->toBeNull()
        ->and($log->action)->toBe('report_auto_confirmed')
        ->and($log->user_id)->toBeNull()
        ->and($log->ip_address)->toBeNull()
        ->and($log->old_values)->toBe(['confirmed_at' => null]);
});
