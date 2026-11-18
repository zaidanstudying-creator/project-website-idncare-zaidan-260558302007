<?php

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Location;
use App\Models\Report;
use App\Models\ReportAttachment;
use App\Models\ReportStatusHistory;
use App\Models\ReportType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config(['app.report_key' => str_repeat('r', 64)]);
});

function handlingType(string $code): ReportType
{
    return ReportType::query()->create(['code' => $code, 'name' => ucfirst($code)]);
}

function handlingReport(ReportType $type, User $reporter, array $attributes = []): Report
{
    $category = Category::query()->create([
        'report_type_id' => $type->id,
        'name' => 'Kategori '.$type->code,
        'is_active' => true,
    ]);
    $location = Location::query()->create(['name' => 'Gedung Utama', 'is_active' => true]);

    return Report::query()->create([
        'ticket_no' => 'IDN-HANDLE-'.strtoupper($type->code).'-'.fake()->unique()->numerify('####'),
        'reporter_id' => $reporter->id,
        'report_type_id' => $type->id,
        'category_id' => $category->id,
        'location_id' => $location->id,
        'title' => 'Judul '.$type->code,
        'description' => 'Uraian laporan yang perlu ditangani.',
        'assigned_unit' => $type->assignedUnit(),
        ...$attributes,
    ]);
}

it('limits admin report lists and details to each assigned unit', function (): void {
    $student = User::factory()->create(['must_change_password' => false]);
    $facility = handlingReport(handlingType(ReportType::FACILITAS), $student);
    $aspiration = handlingReport(handlingType(ReportType::ASPIRASI), $student);
    $sarpras = User::factory()->create(['role' => UserRole::ADMIN_SARPRAS, 'must_change_password' => false]);
    $kemahasiswaan = User::factory()->create(['role' => UserRole::ADMIN_KEMAHASISWAAN, 'must_change_password' => false]);

    $this->actingAs($sarpras)->get(route('admin.reports.index'))
        ->assertOk()->assertSee($facility->ticket_no)->assertDontSee($aspiration->ticket_no);
    $this->actingAs($kemahasiswaan)->get(route('admin.reports.index'))
        ->assertOk()->assertSee($aspiration->ticket_no)->assertDontSee($facility->ticket_no);
    $this->actingAs($sarpras)->get(route('admin.reports.show', $aspiration))->assertNotFound();
    $this->actingAs($kemahasiswaan)->get(route('admin.reports.show', $facility))->assertNotFound();
});

it('shows a token-owned anonymous violation without loading or displaying reporter identity', function (): void {
    $reporter = User::factory()->create([
        'name' => 'Nama Sangat Rahasia',
        'user_code' => 'ID-IDENTITAS-RAHASIA',
        'must_change_password' => false,
    ]);
    $type = handlingType(ReportType::PELANGGARAN);
    $report = handlingReport($type, $reporter, [
        'reporter_id' => null,
        'reporter_token' => Report::reporterTokenFor($reporter),
        'is_anonymous' => true,
        'requested_visibility' => Report::VISIBILITY_RAHASIA,
        'visibility_status' => Report::VISIBILITY_APPROVED,
        'approved_visibility' => Report::VISIBILITY_RAHASIA,
    ]);
    $kemahasiswaan = User::factory()->create(['role' => UserRole::ADMIN_KEMAHASISWAAN, 'must_change_password' => false]);

    $response = $this->actingAs($kemahasiswaan)->get(route('admin.reports.show', $report));
    $response->assertOk()
        ->assertSee('Anonim, tersembunyi')
        ->assertDontSee('Nama Sangat Rahasia')
        ->assertDontSee('ID-IDENTITAS-RAHASIA');

    expect($response->viewData('report')->relationLoaded('reporter'))->toBeFalse();
});

it('stores a status transition, history, and private after photo atomically', function (): void {
    Storage::fake('local');
    $student = User::factory()->create(['must_change_password' => false]);
    $report = handlingReport(handlingType(ReportType::FACILITAS), $student, ['status' => Report::STATUS_DIPROSES]);
    $admin = User::factory()->create(['role' => UserRole::ADMIN_SARPRAS, 'must_change_password' => false]);

    $this->actingAs($admin)->put(route('admin.reports.status.update', $report), [
        'status' => Report::STATUS_SELESAI,
        'after_photo' => UploadedFile::fake()->image('after.jpg'),
        'note' => 'Perbaikan selesai.',
    ])->assertRedirect(route('admin.reports.show', $report));

    $attachment = ReportAttachment::query()->sole();
    $history = ReportStatusHistory::query()->where('report_id', $report->id)->sole();
    expect($report->fresh()->status)->toBe(Report::STATUS_SELESAI)
        ->and($report->fresh()->completed_at)->not->toBeNull()
        ->and($attachment->kind)->toBe('after')
        ->and($attachment->uploaded_by)->toBe($admin->id)
        ->and($history->from_status)->toBe(Report::STATUS_DIPROSES)
        ->and($history->to_status)->toBe(Report::STATUS_SELESAI)
        ->and($history->changed_by)->toBe($admin->id);
    Storage::disk('local')->assertExists($attachment->file_path);
    Storage::disk('public')->assertMissing($attachment->file_path);
});

it('requires an after photo before completing a facility report and preserves state on failure', function (): void {
    Storage::fake('local');
    $student = User::factory()->create(['must_change_password' => false]);
    $report = handlingReport(handlingType(ReportType::FACILITAS), $student, ['status' => Report::STATUS_DIPROSES]);
    $admin = User::factory()->create(['role' => UserRole::ADMIN_SARPRAS, 'must_change_password' => false]);

    $this->actingAs($admin)->from(route('admin.reports.show', $report))
        ->put(route('admin.reports.status.update', $report), ['status' => Report::STATUS_SELESAI])
        ->assertRedirect(route('admin.reports.show', $report))
        ->assertSessionHasErrors('after_photo');

    expect($report->fresh()->status)->toBe(Report::STATUS_DIPROSES)
        ->and(ReportStatusHistory::query()->where('report_id', $report->id)->count())->toBe(0)
        ->and(ReportAttachment::query()->count())->toBe(0);
});

it('requires a rejection reason and stores it in the report and history', function (): void {
    $student = User::factory()->create(['must_change_password' => false]);
    $report = handlingReport(handlingType(ReportType::ASPIRASI), $student, ['status' => Report::STATUS_DIVERIFIKASI]);
    $admin = User::factory()->create(['role' => UserRole::ADMIN_KEMAHASISWAAN, 'must_change_password' => false]);

    $this->actingAs($admin)->put(route('admin.reports.status.update', $report), ['status' => Report::STATUS_DITOLAK])
        ->assertSessionHasErrors('note');

    $this->put(route('admin.reports.status.update', $report), [
        'status' => Report::STATUS_DITOLAK,
        'note' => 'Alasan penolakan yang jelas.',
    ])->assertRedirect(route('admin.reports.show', $report));

    expect($report->fresh()->status)->toBe(Report::STATUS_DITOLAK)
        ->and($report->fresh()->rejected_reason)->toBe('Alasan penolakan yang jelas.')
        ->and(ReportStatusHistory::query()->where('report_id', $report->id)->sole()->note)
        ->toBe('Alasan penolakan yang jelas.');
});

it('rejects skipped transitions and prevents super admin or students from changing status', function (): void {
    $student = User::factory()->create(['must_change_password' => false]);
    $report = handlingReport(handlingType(ReportType::FACILITAS), $student);
    $sarpras = User::factory()->create(['role' => UserRole::ADMIN_SARPRAS, 'must_change_password' => false]);
    $superAdmin = User::factory()->create(['role' => UserRole::SUPER_ADMIN, 'must_change_password' => false]);

    $this->actingAs($sarpras)->put(route('admin.reports.status.update', $report), ['status' => Report::STATUS_SELESAI])
        ->assertSessionHasErrors('status');
    $this->actingAs($superAdmin)->get(route('admin.reports.show', $report))->assertOk();
    $this->put(route('admin.reports.status.update', $report), ['status' => Report::STATUS_DIVERIFIKASI])
        ->assertForbidden();
    $this->actingAs($student)->put(route('admin.reports.status.update', $report), ['status' => Report::STATUS_DIVERIFIKASI])
        ->assertForbidden();

    expect($report->fresh()->status)->toBe(Report::STATUS_DIKIRIM)
        ->and(ReportStatusHistory::query()->where('report_id', $report->id)->count())->toBe(0);
});
