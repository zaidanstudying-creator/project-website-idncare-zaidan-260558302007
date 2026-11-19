<?php

use App\Enums\UserRole;
use App\Livewire\Pages\Reports\CreateForm;
use App\Models\ActivityLog;
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
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config(['app.report_key' => str_repeat('c', 64)]);
});

function createReportType(string $code): ReportType
{
    return ReportType::query()->create([
        'code' => $code,
        'name' => ucfirst($code),
    ]);
}

function createCategoryFor(ReportType $type, string $name = 'Kategori contoh'): Category
{
    return Category::query()->create([
        'report_type_id' => $type->id,
        'name' => $name,
        'is_active' => true,
    ]);
}

it('stores a facility report with a private photo and routes it to Sarpras', function (): void {
    Storage::fake('local');
    $student = User::factory()->create(['must_change_password' => false]);
    $type = createReportType(ReportType::FACILITAS);
    $category = createCategoryFor($type);
    $location = Location::query()->create(['name' => 'Gedung Utama', 'is_active' => true]);

    $response = $this->actingAs($student)->post(route('reports.store', $type->code), [
        'title' => 'Lampu lorong mati',
        'description' => 'Lampu dekat ruang kelas tidak menyala sejak pagi.',
        'category_id' => $category->id,
        'location_id' => $location->id,
        'photos' => [UploadedFile::fake()->image('lampu.jpg')],
        'requested_visibility' => 'umum',
        'is_anonymous' => '1',
    ]);

    $response->assertRedirect(route('reports.index'));
    $report = Report::query()->sole();
    $attachment = ReportAttachment::query()->sole();
    $history = ReportStatusHistory::query()->sole();

    expect($report->reporter_id)->toBe($student->id)
        ->and($report->reporter_token)->toBeNull()
        ->and($report->ticket_no)->toMatch('/^IDN-\d{6}-[A-Z0-9]{8}$/')
        ->and($report->assigned_unit)->toBe(ReportType::UNIT_SARPRAS)
        ->and($report->status)->toBe(Report::STATUS_DIKIRIM)
        ->and($report->requested_visibility)->toBe(Report::VISIBILITY_UMUM)
        ->and($report->visibility_status)->toBe(Report::VISIBILITY_PENDING)
        ->and($report->approved_visibility)->toBeNull()
        ->and($report->is_anonymous)->toBeTrue()
        ->and($attachment->kind)->toBe('before')
        ->and($attachment->uploaded_by)->toBe($student->id)
        ->and($history->to_status)->toBe(Report::STATUS_DIKIRIM)
        ->and($history->changed_by)->toBe($student->id);

    Storage::disk('local')->assertExists($attachment->file_path);
    Storage::disk('public')->assertMissing($attachment->file_path);
});

it('stores aspirations without AI suggestions or requiring a location or photo', function (): void {
    Storage::fake('local');
    config(['services.groq.enabled' => false, 'services.groq.key' => null]);
    $student = User::factory()->create(['must_change_password' => false]);
    $type = createReportType(ReportType::ASPIRASI);
    $category = createCategoryFor($type);

    $this->actingAs($student)->post(route('reports.store', $type->code), [
        'title' => 'Tambah ruang diskusi',
        'description' => 'Usulan ruang diskusi untuk mahasiswa.',
        'category_id' => $category->id,
        'requested_visibility' => 'rahasia',
    ])->assertRedirect(route('reports.index'));

    $report = Report::query()->sole();

    expect($report->reporter_id)->toBe($student->id)
        ->and($report->reporter_token)->toBeNull()
        ->and($report->location_id)->toBeNull()
        ->and($report->assigned_unit)->toBe(ReportType::UNIT_KEMAHASISWAAN)
        ->and($report->requested_visibility)->toBe(Report::VISIBILITY_RAHASIA)
        ->and($report->visibility_status)->toBe(Report::VISIBILITY_PENDING)
        ->and(ReportAttachment::query()->count())->toBe(0);
});

it('stores anonymous violations without reporter identity in related records', function (): void {
    Storage::fake('local');
    $student = User::factory()->create(['must_change_password' => false]);
    $type = createReportType(ReportType::PELANGGARAN);
    $category = createCategoryFor($type);
    $location = Location::query()->create(['name' => 'Perpustakaan', 'is_active' => true]);

    $response = $this->actingAs($student)->post(route('reports.store', $type->code), [
        'title' => 'Dugaan kejadian',
        'description' => 'Saya melihat kejadian pada area kampus.',
        'category_id' => $category->id,
        'location_id' => $location->id,
        'incident_date' => now()->toDateString(),
        'related_party' => 'Pihak terkait',
        'photos' => [UploadedFile::fake()->image('bukti.png')],
        'is_anonymous' => '1',
    ]);

    $response->assertRedirect(route('reports.index'))
        ->assertSessionHas('status');
    $report = Report::query()->sole();
    $attachment = ReportAttachment::query()->sole();
    $history = ReportStatusHistory::query()->sole();
    $activityLog = ActivityLog::query()->where('action', 'report_submitted')->sole();
    $anonymousLogs = ActivityLog::query()->where('subject_id', $report->id)->get();
    $reporterToken = hash_hmac('sha256', (string) $student->id, str_repeat('c', 64));

    expect($report->reporter_id)->toBeNull()
        ->and($report->reporter_token)->toBe($reporterToken)
        ->and($report->assigned_unit)->toBe(ReportType::UNIT_KEMAHASISWAAN)
        ->and($report->requested_visibility)->toBe(Report::VISIBILITY_RAHASIA)
        ->and($report->visibility_status)->toBe(Report::VISIBILITY_APPROVED)
        ->and($report->approved_visibility)->toBe(Report::VISIBILITY_RAHASIA)
        ->and($report->is_anonymous)->toBeTrue()
        ->and($attachment->kind)->toBe('evidence')
        ->and($attachment->uploaded_by)->toBeNull()
        ->and($history->changed_by)->toBeNull()
        ->and($activityLog->user_id)->toBeNull()
        ->and($activityLog->ip_address)->toBeNull()
        ->and($activityLog->subject_id)->toBe($report->id)
        ->and($activityLog->new_values)->toBe(['status' => Report::STATUS_DIKIRIM, 'report_type' => ReportType::PELANGGARAN])
        ->and($anonymousLogs->pluck('user_id')->unique()->all())->toBe([null])
        ->and($anonymousLogs->pluck('ip_address')->unique()->all())->toBe([null]);

    Storage::disk('local')->assertExists($attachment->file_path);
});

it('returns a validation message instead of a server error when anonymous reporting lacks its key', function (): void {
    config(['app.report_key' => null]);
    $student = User::factory()->create(['must_change_password' => false]);
    $type = createReportType(ReportType::PELANGGARAN);
    $category = createCategoryFor($type);
    $location = Location::query()->create(['name' => 'Perpustakaan', 'is_active' => true]);

    $this->from(route('reports.create.form', $type->code))
        ->actingAs($student)
        ->post(route('reports.store', $type->code), [
            'title' => 'Dugaan kejadian',
            'description' => 'Deskripsi laporan untuk uji konfigurasi anonim.',
            'category_id' => $category->id,
            'location_id' => $location->id,
            'incident_date' => now()->toDateString(),
            'is_anonymous' => '1',
        ])
        ->assertRedirect(route('reports.create.form', $type->code))
        ->assertSessionHasErrors([
            'is_anonymous' => 'Pelaporan anonim sementara belum tersedia karena konfigurasi perlindungan belum siap. Hubungi administrator; laporan belum terkirim.',
        ]);

    expect(Report::query()->count())->toBe(0)
        ->and(ActivityLog::query()->count())->toBe(0);
});

it('rejects an attempt to make a violation public and rejects invalid facility input', function (): void {
    $student = User::factory()->create(['must_change_password' => false]);
    $facility = createReportType(ReportType::FACILITAS);
    $facilityCategory = createCategoryFor($facility);
    $violation = createReportType(ReportType::PELANGGARAN);
    $violationCategory = createCategoryFor($violation);
    $location = Location::query()->create(['name' => 'Gedung Utama', 'is_active' => true]);

    $this->actingAs($student)->post(route('reports.store', $violation->code), [
        'title' => 'Percobaan visibilitas',
        'description' => 'Isi laporan.',
        'category_id' => $violationCategory->id,
        'location_id' => $location->id,
        'incident_date' => now()->toDateString(),
        'requested_visibility' => 'umum',
    ])->assertSessionHasErrors('requested_visibility');

    $this->post(route('reports.store', $facility->code), [
        'title' => 'Foto belum dilampirkan',
        'description' => 'Isi laporan.',
        'category_id' => $facilityCategory->id,
        'location_id' => $location->id,
        'requested_visibility' => 'rahasia',
    ])->assertSessionHasErrors('photos');

    expect(Report::query()->count())->toBe(0);
});

it('rejects a valid image payload whose filename extension is outside the allowlist', function (): void {
    $student = User::factory()->create(['must_change_password' => false]);
    $type = createReportType(ReportType::FACILITAS);
    $category = createCategoryFor($type);
    $location = Location::query()->create(['name' => 'Gedung Utama', 'is_active' => true]);

    Livewire::actingAs($student)
        ->test(CreateForm::class, ['type' => $type->code])
        ->set('title', 'Foto tidak sesuai')
        ->set('description', 'Uji ekstensi foto yang tidak diizinkan.')
        ->set('categoryId', (string) $category->id)
        ->set('locationId', (string) $location->id)
        ->set('requestedVisibility', 'rahasia')
        ->set('photos', [UploadedFile::fake()->image('payload.svg')])
        ->call('submit')
        ->assertHasErrors(['photos.0' => 'extensions']);

    expect(Report::query()->count())->toBe(0);
});

it('lists only reports owned by the student including anonymous reports through the token', function (): void {
    $student = User::factory()->create(['must_change_password' => false]);
    $otherStudent = User::factory()->create(['must_change_password' => false]);
    $type = createReportType(ReportType::PELANGGARAN);
    $category = createCategoryFor($type);

    $owned = Report::query()->create([
        'ticket_no' => 'IDN-OWNED-0001',
        'reporter_id' => null,
        'reporter_token' => Report::reporterTokenFor($student),
        'report_type_id' => $type->id,
        'category_id' => $category->id,
        'title' => 'Laporan anonim milik saya',
        'description' => 'Rahasia.',
        'incident_date' => now()->toDateString(),
        'requested_visibility' => Report::VISIBILITY_RAHASIA,
        'visibility_status' => Report::VISIBILITY_APPROVED,
        'approved_visibility' => Report::VISIBILITY_RAHASIA,
        'is_anonymous' => true,
        'assigned_unit' => ReportType::UNIT_KEMAHASISWAAN,
    ]);
    Report::query()->create([
        'ticket_no' => 'IDN-OTHER-0001',
        'reporter_id' => $otherStudent->id,
        'report_type_id' => $type->id,
        'category_id' => $category->id,
        'title' => 'Laporan orang lain',
        'description' => 'Rahasia.',
        'incident_date' => now()->toDateString(),
        'requested_visibility' => Report::VISIBILITY_RAHASIA,
        'visibility_status' => Report::VISIBILITY_APPROVED,
        'approved_visibility' => Report::VISIBILITY_RAHASIA,
        'assigned_unit' => ReportType::UNIT_KEMAHASISWAAN,
    ]);

    $this->actingAs($student)->get(route('reports.index'))
        ->assertOk()
        ->assertSee($owned->ticket_no)
        ->assertDontSee('IDN-OTHER-0001')
        ->assertDontSee($otherStudent->name);
});

it('authorizes private attachment access through the report policy', function (): void {
    Storage::fake('local');
    config(['app.report_key' => str_repeat('d', 64)]);
    $student = User::factory()->create(['must_change_password' => false]);
    $otherStudent = User::factory()->create(['must_change_password' => false]);
    $type = createReportType(ReportType::FACILITAS);
    $category = createCategoryFor($type);
    $report = Report::query()->create([
        'ticket_no' => 'IDN-ATTACH-0001',
        'reporter_id' => $student->id,
        'report_type_id' => $type->id,
        'category_id' => $category->id,
        'title' => 'Lampiran privat',
        'description' => 'Deskripsi.',
        'assigned_unit' => ReportType::UNIT_SARPRAS,
    ]);
    Storage::disk('local')->put('reports/private.png', 'private photo');
    $attachment = ReportAttachment::query()->create([
        'report_id' => $report->id,
        'file_path' => 'reports/private.png',
        'kind' => 'before',
        'uploaded_by' => $student->id,
    ]);

    $this->actingAs($student)->get(route('attachments.show', $attachment))
        ->assertOk()
        ->assertHeaderContains('Cache-Control', 'no-store')
        ->assertSee('data:image/png;base64,')
        ->assertDontSee($student->user_code);

    $this->actingAs($otherStudent)->get(route('attachments.show', $attachment))->assertForbidden();

    $sarpras = User::factory()->create([
        'role' => UserRole::ADMIN_SARPRAS,
        'must_change_password' => false,
    ]);
    $kemah = User::factory()->create([
        'role' => UserRole::ADMIN_KEMAHASISWAAN,
        'must_change_password' => false,
    ]);

    $this->actingAs($sarpras)->get(route('attachments.show', $attachment))->assertOk();
    $this->actingAs($kemah)->get(route('attachments.show', $attachment))->assertForbidden();

    $violation = createReportType(ReportType::PELANGGARAN);
    $violationCategory = createCategoryFor($violation, 'Etika');
    $anonymousReport = Report::query()->create([
        'ticket_no' => 'IDN-ATTACH-0002',
        'reporter_id' => null,
        'reporter_token' => Report::reporterTokenFor($student),
        'report_type_id' => $violation->id,
        'category_id' => $violationCategory->id,
        'title' => 'Laporan rahasia',
        'description' => 'Isi rahasia.',
        'incident_date' => now()->toDateString(),
        'requested_visibility' => Report::VISIBILITY_RAHASIA,
        'visibility_status' => Report::VISIBILITY_APPROVED,
        'approved_visibility' => Report::VISIBILITY_RAHASIA,
        'is_anonymous' => true,
        'assigned_unit' => ReportType::UNIT_KEMAHASISWAAN,
    ]);
    Storage::disk('local')->put('reports/evidence.png', 'private evidence');
    $evidence = ReportAttachment::query()->create([
        'report_id' => $anonymousReport->id,
        'file_path' => 'reports/evidence.png',
        'kind' => 'evidence',
        'uploaded_by' => null,
    ]);

    $this->actingAs($student)->get(route('attachments.show', $evidence))->assertOk();
    $this->actingAs($otherStudent)->get(route('attachments.show', $evidence))->assertForbidden();
});

it('limits report creation routes to students and requires completed first login', function (): void {
    createReportType(ReportType::FACILITAS);
    createReportType(ReportType::ASPIRASI);
    createReportType(ReportType::PELANGGARAN);
    $student = User::factory()->create(['must_change_password' => false]);
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN_SARPRAS,
        'must_change_password' => false,
    ]);
    $newStudent = User::factory()->create(['must_change_password' => true]);

    $this->actingAs($student)->get(route('reports.create'))->assertOk();
    $this->actingAs($admin)->get(route('reports.create'))->assertForbidden();
    $this->actingAs($newStudent)->get(route('reports.create'))->assertRedirect(route('password.change'));
});

it('renders all three report forms with their applicable fields and privacy controls', function (): void {
    $student = User::factory()->create(['must_change_password' => false]);
    $facility = createReportType(ReportType::FACILITAS);
    $aspiration = createReportType(ReportType::ASPIRASI);
    $violation = createReportType(ReportType::PELANGGARAN);
    createCategoryFor($facility, 'Lampu');
    createCategoryFor($aspiration, 'Akademik');
    createCategoryFor($violation, 'Etika');
    Location::query()->create(['name' => 'Gedung Utama', 'is_active' => true]);

    $this->actingAs($student)->get(route('reports.create.form', $facility->code))
        ->assertOk()
        ->assertSee('Foto kerusakan')
        ->assertSee('Wajib minimal satu foto.');

    $this->get(route('reports.create.form', $aspiration->code))
        ->assertOk()
        ->assertSee('Lokasi terkait (opsional)')
        ->assertSee('Foto pendukung (opsional)');

    $this->get(route('reports.create.form', $violation->code))
        ->assertOk()
        ->assertSee('Rahasia (wajib)')
        ->assertSee('Kirim sebagai anonim')
        ->assertDontSee('name="requested_visibility"');
});
