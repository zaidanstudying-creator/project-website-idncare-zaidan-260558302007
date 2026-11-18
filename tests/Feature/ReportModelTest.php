<?php

use App\Enums\UserRole;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Location;
use App\Models\Report;
use App\Models\ReportAttachment;
use App\Models\ReportStatusHistory;
use App\Models\ReportType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createScopedReport(ReportType $type, User $reporter, array $attributes = []): Report
{
    $category = Category::create([
        'report_type_id' => $type->id,
        'name' => 'Kategori '.$type->code,
        'is_active' => true,
    ]);

    return Report::create([
        'ticket_no' => 'IDN-'.strtoupper($type->code).'-'.fake()->unique()->numerify('####'),
        'reporter_id' => $reporter->id,
        'report_type_id' => $type->id,
        'category_id' => $category->id,
        'title' => 'Laporan demo',
        'description' => 'Isi laporan demo untuk pengujian.',
        'assigned_unit' => $type->assignedUnit(),
        ...$attributes,
    ]);
}

it('lets a reporter retrieve their anonymous violation using only the HMAC token', function (): void {
    config(['app.report_key' => str_repeat('a', 64)]);
    $reporter = User::factory()->create();
    $type = ReportType::create(['code' => ReportType::PELANGGARAN, 'name' => 'Laporan Dugaan Pelanggaran']);
    $token = hash_hmac('sha256', (string) $reporter->id, str_repeat('a', 64));

    $report = createScopedReport($type, $reporter, [
        'reporter_id' => null,
        'reporter_token' => $token,
        'is_anonymous' => true,
        'requested_visibility' => Report::VISIBILITY_RAHASIA,
        'visibility_status' => Report::VISIBILITY_APPROVED,
        'approved_visibility' => Report::VISIBILITY_RAHASIA,
    ]);

    expect(Report::ownedBy($reporter)->sole()->is($report))->toBeTrue()
        ->and(Report::ownedBy(User::factory()->create())->count())->toBe(0)
        ->and($report->reporter)->toBeNull()
        ->and($report->reporter_id)->toBeNull();
});

it('keeps student report scopes available without the token key and never treats anonymous records as owned', function (): void {
    config(['app.report_key' => null]);
    $reporter = User::factory()->create();
    $otherReporter = User::factory()->create();
    $aspiration = ReportType::create(['code' => ReportType::ASPIRASI, 'name' => 'Aspirasi']);
    $violation = ReportType::create(['code' => ReportType::PELANGGARAN, 'name' => 'Laporan Dugaan Pelanggaran']);

    $owned = createScopedReport($aspiration, $reporter);
    $public = createScopedReport($aspiration, $otherReporter, [
        'requested_visibility' => Report::VISIBILITY_UMUM,
        'visibility_status' => Report::VISIBILITY_APPROVED,
        'approved_visibility' => Report::VISIBILITY_UMUM,
    ]);
    $anonymous = createScopedReport($violation, $reporter, [
        'reporter_id' => null,
        'reporter_token' => str_repeat('a', 64),
        'is_anonymous' => true,
        'requested_visibility' => Report::VISIBILITY_RAHASIA,
        'visibility_status' => Report::VISIBILITY_APPROVED,
        'approved_visibility' => Report::VISIBILITY_RAHASIA,
    ]);

    $ownedReports = Report::query()->ownedBy($reporter)->get();
    $visibleReports = Report::query()->visibleTo($reporter)->get();

    expect($ownedReports->modelKeys())->toContain($owned->id)
        ->and($ownedReports->modelKeys())->not->toContain($anonymous->id)
        ->and($visibleReports->modelKeys())->toContain($owned->id, $public->id)
        ->and($visibleReports->modelKeys())->not->toContain($anonymous->id);
});

it('shows only approved public non-violation reports in the public scope', function (): void {
    $reporter = User::factory()->create();
    $facility = ReportType::create(['code' => ReportType::FACILITAS, 'name' => 'Laporan Fasilitas']);
    $violation = ReportType::create(['code' => ReportType::PELANGGARAN, 'name' => 'Laporan Dugaan Pelanggaran']);

    $publicFacility = createScopedReport($facility, $reporter, [
        'requested_visibility' => Report::VISIBILITY_UMUM,
        'visibility_status' => Report::VISIBILITY_APPROVED,
        'approved_visibility' => Report::VISIBILITY_UMUM,
    ]);
    createScopedReport($facility, $reporter, [
        'requested_visibility' => Report::VISIBILITY_UMUM,
        'visibility_status' => Report::VISIBILITY_PENDING,
    ]);
    createScopedReport($violation, $reporter, [
        'requested_visibility' => Report::VISIBILITY_UMUM,
        'visibility_status' => Report::VISIBILITY_APPROVED,
        'approved_visibility' => Report::VISIBILITY_UMUM,
    ]);

    expect(Report::query()->public()->sole()->is($publicFacility))->toBeTrue();
});

it('limits unit admins and lets students see owned and public reports', function (): void {
    config(['app.report_key' => str_repeat('b', 64)]);
    $student = User::factory()->create();
    $otherStudent = User::factory()->create();
    $sarprasAdmin = User::factory()->create(['role' => UserRole::ADMIN_SARPRAS]);
    $kemahasiswaanAdmin = User::factory()->create(['role' => UserRole::ADMIN_KEMAHASISWAAN]);
    $facility = ReportType::create(['code' => ReportType::FACILITAS, 'name' => 'Laporan Fasilitas']);
    $aspiration = ReportType::create(['code' => ReportType::ASPIRASI, 'name' => 'Aspirasi']);
    $violation = ReportType::create(['code' => ReportType::PELANGGARAN, 'name' => 'Laporan Dugaan Pelanggaran']);

    $ownedPrivate = createScopedReport($facility, $student);
    $otherPublic = createScopedReport($aspiration, $otherStudent, [
        'requested_visibility' => Report::VISIBILITY_UMUM,
        'visibility_status' => Report::VISIBILITY_APPROVED,
        'approved_visibility' => Report::VISIBILITY_UMUM,
    ]);
    $anonymousViolation = createScopedReport($violation, $otherStudent, [
        'reporter_id' => null,
        'reporter_token' => Report::reporterTokenFor($otherStudent),
        'is_anonymous' => true,
        'requested_visibility' => Report::VISIBILITY_RAHASIA,
        'visibility_status' => Report::VISIBILITY_APPROVED,
        'approved_visibility' => Report::VISIBILITY_RAHASIA,
    ]);

    expect(Report::visibleTo($sarprasAdmin)->pluck('id')->all())->toBe([$ownedPrivate->id])
        ->and(Report::visibleTo($kemahasiswaanAdmin)->pluck('id')->all())->toBe([$otherPublic->id, $anonymousViolation->id])
        ->and(Report::visibleTo($student)->pluck('id')->all())->toBe([$ownedPrivate->id, $otherPublic->id]);
});

it('connects reports to private attachment metadata and status history', function (): void {
    $reporter = User::factory()->create();
    $type = ReportType::create(['code' => ReportType::FACILITAS, 'name' => 'Laporan Fasilitas']);
    $location = Location::create(['name' => 'Gedung Utama', 'is_active' => true]);
    $report = createScopedReport($type, $reporter, ['location_id' => $location->id]);
    $attachment = ReportAttachment::create([
        'report_id' => $report->id,
        'file_path' => 'reports/demo.png',
        'kind' => 'before',
        'uploaded_by' => $reporter->id,
    ]);
    $history = ReportStatusHistory::create([
        'report_id' => $report->id,
        'from_status' => null,
        'to_status' => Report::STATUS_DIKIRIM,
        'changed_by' => $reporter->id,
    ]);

    expect($report->location->is($location))->toBeTrue()
        ->and($report->attachments->sole()->is($attachment))->toBeTrue()
        ->and($report->statusHistories->sole()->is($history))->toBeTrue()
        ->and(config('filesystems.disks.local.root'))->toEndWith('storage/app/private');
});

it('does not allow activity log records to be updated', function (): void {
    $log = ActivityLog::create(['action' => 'report.submitted']);

    expect(fn () => $log->update(['action' => 'report.changed']))
        ->toThrow(LogicException::class);
});

it('does not allow activity log records to be deleted', function (): void {
    $log = ActivityLog::create(['action' => 'report.submitted']);

    expect(fn () => $log->delete())
        ->toThrow(LogicException::class);
});
