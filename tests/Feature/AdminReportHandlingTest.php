<?php

use App\Enums\UserRole;
use App\Livewire\Pages\Admin\Reports\Show as AdminReportShow;
use App\Models\Category;
use App\Models\Location;
use App\Models\Report;
use App\Models\ReportAttachment;
use App\Models\ReportStatusHistory;
use App\Models\ReportType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

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

it('shows each report type to every account assigned to its admin role', function (): void {
    Storage::fake('local');
    $student = User::factory()->create(['must_change_password' => false]);
    $facility = handlingReport(handlingType(ReportType::FACILITAS), $student, [
        'assigned_unit' => ReportType::UNIT_KEMAHASISWAAN,
    ]);
    $aspiration = handlingReport(handlingType(ReportType::ASPIRASI), $student, [
        'assigned_unit' => ReportType::UNIT_SARPRAS,
    ]);
    $violation = handlingReport(handlingType(ReportType::PELANGGARAN), $student, [
        'assigned_unit' => ReportType::UNIT_SARPRAS,
        'reporter_id' => null,
        'reporter_token' => Report::reporterTokenFor($student),
        'is_anonymous' => true,
        'requested_visibility' => Report::VISIBILITY_RAHASIA,
        'visibility_status' => Report::VISIBILITY_APPROVED,
        'approved_visibility' => Report::VISIBILITY_RAHASIA,
    ]);
    $evidencePath = UploadedFile::fake()->image('evidence.png')->store('reports', 'local');
    $violationAttachment = $violation->attachments()->create([
        'file_path' => $evidencePath,
        'kind' => 'evidence',
    ]);
    $sarpras = User::factory()->create(['role' => UserRole::ADMIN_SARPRAS, 'must_change_password' => false]);
    $anotherSarpras = User::factory()->create(['role' => UserRole::ADMIN_SARPRAS, 'must_change_password' => false]);
    $kemahasiswaan = User::factory()->create(['role' => UserRole::ADMIN_KEMAHASISWAAN, 'must_change_password' => false]);
    $anotherKemahasiswaan = User::factory()->create(['role' => UserRole::ADMIN_KEMAHASISWAAN, 'must_change_password' => false]);
    $superAdmin = User::factory()->create(['role' => UserRole::SUPER_ADMIN, 'must_change_password' => false]);

    $this->actingAs($sarpras)->get(route('admin.reports.index'))
        ->assertOk()->assertSee($facility->ticket_no)->assertDontSee($aspiration->ticket_no)->assertDontSee($violation->ticket_no);
    $this->actingAs($anotherSarpras)->get(route('admin.reports.index'))
        ->assertOk()->assertSee($facility->ticket_no)->assertDontSee($aspiration->ticket_no)->assertDontSee($violation->ticket_no);
    $this->actingAs($kemahasiswaan)->get(route('admin.reports.index'))
        ->assertOk()->assertSee($aspiration->ticket_no)->assertSee($violation->ticket_no)->assertDontSee($facility->ticket_no);
    $this->actingAs($anotherKemahasiswaan)->get(route('admin.reports.index'))
        ->assertOk()->assertSee($aspiration->ticket_no)->assertSee($violation->ticket_no)->assertDontSee($facility->ticket_no);
    $this->actingAs($superAdmin)->get(route('admin.reports.index'))
        ->assertOk()->assertSee($facility->ticket_no)->assertSee($aspiration->ticket_no)->assertSee($violation->ticket_no);
    $this->actingAs($sarpras)->get(route('admin.reports.show', $aspiration))->assertNotFound();
    $this->actingAs($kemahasiswaan)->get(route('admin.reports.show', $facility))->assertNotFound();
    $this->actingAs($anotherKemahasiswaan)->get(route('admin.reports.show', $aspiration))->assertOk();
    $this->actingAs($anotherKemahasiswaan)->get(route('admin.reports.show', $violation))->assertOk();
    $this->actingAs($kemahasiswaan)->get(route('attachments.show', $violationAttachment))
        ->assertOk()->assertSee('data:image/png;base64,', false);
    $this->actingAs($anotherKemahasiswaan)->get(route('attachments.show', $violationAttachment))
        ->assertOk()->assertSee('data:image/png;base64,', false);
    $this->actingAs($superAdmin)->get(route('admin.reports.show', $facility))->assertOk();
    $this->actingAs($superAdmin)->get(route('admin.reports.show', $aspiration))->assertOk();
    $this->actingAs($superAdmin)->get(route('admin.reports.show', $violation))->assertOk();
    $this->actingAs($superAdmin)->get(route('attachments.show', $violationAttachment))
        ->assertOk()->assertSee('data:image/png;base64,', false);
    $this->actingAs($sarpras)->get(route('attachments.show', $violationAttachment))->assertForbidden();
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

    $this->withSession(['auth_version' => $kemahasiswaan->auth_version]);
    $renderedReport = Livewire::test(AdminReportShow::class, ['report' => $report])
        ->instance()->render()->getData()['report'];
    expect($renderedReport->relationLoaded('reporter'))->toBeFalse();
});

it('stores a status transition, history, and private after photo atomically', function (): void {
    Storage::fake('local');
    $student = User::factory()->create(['must_change_password' => false]);
    $report = handlingReport(handlingType(ReportType::FACILITAS), $student, ['status' => Report::STATUS_DIPROSES]);
    $admin = User::factory()->create(['role' => UserRole::ADMIN_SARPRAS, 'must_change_password' => false]);

    $this->actingAs($admin)->withSession(['auth_version' => $admin->auth_version]);
    Livewire::test(AdminReportShow::class, ['report' => $report])
        ->set('status', Report::STATUS_SELESAI)
        ->set('afterPhoto', UploadedFile::fake()->image('after.jpg'))
        ->set('note', 'Perbaikan selesai.')
        ->call('updateStatus')->assertHasNoErrors();

    $attachment = ReportAttachment::query()->sole();
    $history = ReportStatusHistory::query()->where('report_id', $report->id)->sole();
    expect($report->fresh()->status)->toBe(Report::STATUS_SELESAI)
        ->and($report->fresh()->completed_at)->not->toBeNull()
        ->and($attachment->kind)->toBe('after')
        ->and($attachment->uploaded_by)->toBe($admin->id)
        ->and($history->from_status)->toBe(Report::STATUS_DIPROSES)
        ->and($history->to_status)->toBe(Report::STATUS_SELESAI)
        ->and($history->changed_by)->toBe($admin->id)
        ->and($history->created_at)->not->toBeNull();
    Storage::disk('local')->assertExists($attachment->file_path);
    Storage::disk('public')->assertMissing($attachment->file_path);
});

it('shows the report creation time for a legacy submission history without a timestamp', function (): void {
    $this->travelTo(Carbon::create(2026, 11, 26, 10, 15, 0, 'Asia/Jakarta'));
    $student = User::factory()->create(['must_change_password' => false]);
    $report = handlingReport(handlingType(ReportType::FACILITAS), $student, ['status' => Report::STATUS_DIKIRIM]);
    ReportStatusHistory::query()->create([
        'report_id' => $report->id,
        'from_status' => null,
        'to_status' => Report::STATUS_DIKIRIM,
        'changed_by' => $student->id,
    ]);
    $admin = User::factory()->create(['role' => UserRole::ADMIN_SARPRAS, 'must_change_password' => false]);

    $response = $this->actingAs($admin)
        ->withSession(['auth_version' => $admin->auth_version])
        ->get(route('admin.reports.show', $report));

    $response->assertOk()
        ->assertSee('26 Nov 2026')
        ->assertSee('10:15 WIB')
        ->assertDontSee('Waktu tidak tersedia');
});

it('requires an after photo before completing a facility report and preserves state on failure', function (): void {
    Storage::fake('local');
    $student = User::factory()->create(['must_change_password' => false]);
    $report = handlingReport(handlingType(ReportType::FACILITAS), $student, ['status' => Report::STATUS_DIPROSES]);
    $admin = User::factory()->create(['role' => UserRole::ADMIN_SARPRAS, 'must_change_password' => false]);

    $this->actingAs($admin)->withSession(['auth_version' => $admin->auth_version]);
    Livewire::test(AdminReportShow::class, ['report' => $report])
        ->set('status', Report::STATUS_SELESAI)
        ->call('updateStatus')->assertHasErrors('afterPhoto');

    expect($report->fresh()->status)->toBe(Report::STATUS_DIPROSES)
        ->and(ReportStatusHistory::query()->where('report_id', $report->id)->count())->toBe(0)
        ->and(ReportAttachment::query()->count())->toBe(0);
});

it('shows the after photo requirement beside the upload field when completion lacks evidence', function (): void {
    $student = User::factory()->create(['must_change_password' => false]);
    $report = handlingReport(handlingType(ReportType::FACILITAS), $student, ['status' => Report::STATUS_DIPROSES]);
    $admin = User::factory()->create(['role' => UserRole::ADMIN_SARPRAS, 'must_change_password' => false]);

    $this->actingAs($admin)->withSession(['auth_version' => $admin->auth_version]);

    Livewire::test(AdminReportShow::class, ['report' => $report])
        ->set('status', Report::STATUS_SELESAI)
        ->call('updateStatus')
        ->assertHasErrors('afterPhoto')
        ->assertSee('Unggah minimal satu foto sesudah sebelum laporan fasilitas diselesaikan.');

    expect($report->fresh()->status)->toBe(Report::STATUS_DIPROSES)
        ->and(ReportStatusHistory::query()->where('report_id', $report->id)->count())->toBe(0)
        ->and(ReportAttachment::query()->count())->toBe(0);
});

it('requires a rejection reason and stores it in the report and history', function (): void {
    $student = User::factory()->create(['must_change_password' => false]);
    $report = handlingReport(handlingType(ReportType::ASPIRASI), $student, ['status' => Report::STATUS_DIVERIFIKASI]);
    $admin = User::factory()->create(['role' => UserRole::ADMIN_KEMAHASISWAAN, 'must_change_password' => false]);

    $this->actingAs($admin)->withSession(['auth_version' => $admin->auth_version]);
    Livewire::test(AdminReportShow::class, ['report' => $report])
        ->set('status', Report::STATUS_DITOLAK)
        ->call('updateStatus')->assertHasErrors('note');

    Livewire::test(AdminReportShow::class, ['report' => $report])
        ->set('status', Report::STATUS_DITOLAK)
        ->set('note', 'Alasan penolakan yang jelas.')
        ->call('updateStatus')->assertHasNoErrors();

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

    $this->actingAs($sarpras)->withSession(['auth_version' => $sarpras->auth_version]);
    Livewire::test(AdminReportShow::class, ['report' => $report])
        ->set('status', Report::STATUS_SELESAI)
        ->call('updateStatus')->assertHasErrors('status');
    $this->actingAs($superAdmin)->get(route('admin.reports.show', $report))->assertOk();
    $this->withSession(['auth_version' => $superAdmin->auth_version]);
    Livewire::test(AdminReportShow::class, ['report' => $report])
        ->set('status', Report::STATUS_DIVERIFIKASI)
        ->call('updateStatus')->assertForbidden();
    $this->actingAs($student)->withSession(['auth_version' => $student->auth_version]);
    $this->get(route('admin.reports.show', $report))->assertForbidden();

    expect($report->fresh()->status)->toBe(Report::STATUS_DIKIRIM)
        ->and(ReportStatusHistory::query()->where('report_id', $report->id)->count())->toBe(0);
});
