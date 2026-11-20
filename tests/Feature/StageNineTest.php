<?php

use App\Enums\StudyProgram;
use App\Enums\UserRole;
use App\Livewire\Components\Admin\UserTable;
use App\Livewire\Pages\Admin\Archive\Index as AdminArchiveIndex;
use App\Livewire\Pages\Admin\Users\EditAccount as AdminUsersEditAccount;
use App\Livewire\Pages\Admin\Users\Import as AdminUsersImport;
use App\Livewire\Pages\Admin\Users\Index as AdminUsersIndex;
use App\Livewire\Pages\Notifications\Index as NotificationsIndex;
use App\Livewire\Pages\Reports\CreateForm;
use App\Livewire\Pages\Reports\Show as StudentReportShow;
use App\Models\Category;
use App\Models\Report;
use App\Models\ReportType;
use App\Models\User;
use App\Services\GroqTitleSuggestionService;
use App\Services\ReportStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function stageNineAdmin(): User
{
    return User::factory()->create(['role' => UserRole::SUPER_ADMIN, 'must_change_password' => false]);
}

function stageNineReport(User $reporter): Report
{
    $type = ReportType::firstOrCreate(['code' => ReportType::ASPIRASI], ['name' => 'Aspirasi']);
    $category = Category::firstOrCreate(['report_type_id' => $type->id, 'name' => 'Akademik'], ['is_active' => true]);

    return Report::create([
        'ticket_no' => 'IDN-'.strtoupper(bin2hex(random_bytes(4))),
        'reporter_id' => $reporter->id,
        'report_type_id' => $type->id,
        'category_id' => $category->id,
        'title' => 'Usulan layanan kampus',
        'description' => 'Mohon tinjau usulan ini.',
        'status' => Report::STATUS_DIKIRIM,
        'requested_visibility' => Report::VISIBILITY_RAHASIA,
        'visibility_status' => Report::VISIBILITY_PENDING,
        'assigned_unit' => ReportType::UNIT_KEMAHASISWAAN,
    ]);
}

it('shows the available study programs on the student account form', function (): void {
    $admin = stageNineAdmin();
    $response = $this->actingAs($admin)
        ->withSession(['auth_version' => $admin->auth_version])
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('Pilih program studi');

    foreach (StudyProgram::cases() as $program) {
        $response->assertSee($program->value);
    }
});

it('allows only super admins to create accounts and never records generated passwords in activity logs', function (): void {
    $student = User::factory()->create(['must_change_password' => false]);
    $payload = ['user_code' => 'MHS-NEW-8', 'name' => 'Mahasiswa Baru', 'role' => UserRole::MAHASISWA->value, 'unit_or_prodi' => StudyProgram::TEKNOLOGI_REKAYASA_PERANGKAT_LUNAK->value, 'nim' => '20260008', 'angkatan' => 2026];
    $this->actingAs($student)->get(route('admin.users.index'))->assertForbidden();

    $admin = stageNineAdmin();
    $this->actingAs($admin)->withSession(['auth_version' => $admin->auth_version]);
    $component = Livewire::test(AdminUsersIndex::class);
    foreach ($payload as $field => $value) {
        $component->set($field, (string) $value);
    }
    $component->call('createUser')
        ->assertHasNoErrors()
        ->assertDispatched('account-created')
        ->assertSeeHtml('id="temporary-credentials-dialog" open');
    $created = User::query()->where('user_code', 'MHS-NEW-8')->firstOrFail();
    expect($created->must_change_password)->toBeTrue()
        ->and($created->unit_or_prodi)->toBe(StudyProgram::TEKNOLOGI_REKAYASA_PERANGKAT_LUNAK->value)
        ->and($created->nim)->toBe('20260008')
        ->and($created->angkatan)->toBe(2026)
        ->and($created->is_active)->toBeTrue()
        ->and($component->get('temporaryPassword'))->not->toBeEmpty();
    $log = $admin->activityLogs()->where('action', 'user_created')->firstOrFail();
    expect(json_encode($log->new_values))->not->toContain($component->get('temporaryPassword'));
});

it('requires study program, NIM, and cohort year when creating a student account', function (): void {
    $admin = stageNineAdmin();

    $this->actingAs($admin)->withSession(['auth_version' => $admin->auth_version]);
    Livewire::test(AdminUsersIndex::class)
        ->set('user_code', 'MHS-MISSING-DATA')
        ->set('name', 'Mahasiswa Tanpa Data')
        ->set('role', UserRole::MAHASISWA->value)
        ->call('createUser')->assertHasErrors(['unit_or_prodi', 'nim', 'angkatan']);

    expect(User::query()->where('user_code', 'MHS-MISSING-DATA')->exists())->toBeFalse();

    Livewire::test(AdminUsersIndex::class)
        ->set('user_code', 'MHS-INVALID-PROGRAM')
        ->set('name', 'Mahasiswa Prodi Salah')
        ->set('role', UserRole::MAHASISWA->value)
        ->set('unit_or_prodi', 'Program Studi Tidak Terdaftar')
        ->set('nim', '20260011')
        ->set('angkatan', '2026')
        ->call('createUser')->assertHasErrors('unit_or_prodi');
});

it('lets super admins edit permitted identity details of another student and records the changes', function (): void {
    $admin = stageNineAdmin();
    $student = User::factory()->create([
        'name' => 'Nama Sebelumnya',
        'unit_or_prodi' => StudyProgram::KOMPUTER_JARINGAN->value,
        'nim' => '20250012',
        'angkatan' => 2025,
    ]);

    $this->actingAs($admin)
        ->withSession(['auth_version' => $admin->auth_version])
        ->get(route('admin.users.edit', $student))
        ->assertOk()
        ->assertSee('Edit informasi akun')
        ->assertSee('20250012');

    Livewire::test(AdminUsersEditAccount::class, ['user' => $student])
        ->set('name', 'Nama Diperbarui')
        ->set('unit_or_prodi', StudyProgram::MEDIA_GRAFIS->value)
        ->set('nim', '20260012')
        ->set('angkatan', '2026')
        ->call('saveAccount')->assertHasNoErrors()->assertRedirect(route('admin.users.index'));

    expect($student->fresh()->name)->toBe('Nama Diperbarui')
        ->and($student->fresh()->unit_or_prodi)->toBe(StudyProgram::MEDIA_GRAFIS->value)
        ->and($student->fresh()->nim)->toBe('20260012')
        ->and($student->fresh()->angkatan)->toBe(2026)
        ->and($student->fresh()->role)->toBe(UserRole::MAHASISWA)
        ->and($student->fresh()->user_code)->not->toBe('ID-DIUBAH');

    $log = $admin->activityLogs()->where('action', 'student_account_updated')->firstOrFail();
    expect($log->old_values['nim'])->toBe('20250012')
        ->and($log->new_values['nim'])->toBe('20260012');
});

it('rejects student identity changes from non-super admins and prevents NIM reuse', function (): void {
    $student = User::factory()->create([
        'nim' => '20260013',
        'unit_or_prodi' => StudyProgram::TEKNOLOGI_REKAYASA_PERANGKAT_LUNAK->value,
    ]);
    $otherStudent = User::factory()->create(['nim' => '20260014', 'must_change_password' => false]);
    $payload = [
        'name' => 'Nama Baru',
        'unit_or_prodi' => StudyProgram::KOMPUTER_JARINGAN->value,
        'nim' => '20260014',
        'angkatan' => 2026,
    ];

    $this->actingAs($otherStudent)->withSession(['auth_version' => $otherStudent->auth_version])
        ->get(route('admin.users.edit', $student))->assertForbidden();

    $admin = stageNineAdmin();
    $this->actingAs($admin)->withSession(['auth_version' => $admin->auth_version]);
    Livewire::test(AdminUsersEditAccount::class, ['user' => $student])
        ->set('name', $payload['name'])
        ->set('unit_or_prodi', $payload['unit_or_prodi'])
        ->set('nim', $payload['nim'])
        ->set('angkatan', (string) $payload['angkatan'])
        ->call('saveAccount')->assertHasErrors('nim');

    $staff = User::factory()->create(['role' => UserRole::ADMIN_SARPRAS]);
    $this->get(route('admin.users.edit', $staff))->assertOk()->assertSee('Unit');
});

it('lets super admins edit only the name and unit of another admin account', function (): void {
    $admin = stageNineAdmin();
    $staff = User::factory()->create([
        'user_code' => 'ADMIN-SARPRAS-1',
        'name' => 'Nama Admin Lama',
        'role' => UserRole::ADMIN_SARPRAS,
        'unit_or_prodi' => 'Sarpras lama',
        'is_active' => false,
        'must_change_password' => false,
    ]);
    $originalPassword = $staff->password;

    $this->actingAs($admin)->withSession(['auth_version' => $admin->auth_version])
        ->get(route('admin.users.edit', $staff))->assertOk()->assertSee('Edit informasi akun');

    Livewire::test(AdminUsersEditAccount::class, ['user' => $staff])
        ->set('name', 'Nama Admin Baru')
        ->set('unit_or_prodi', 'Sarpras')
        ->set('nim', 'TIDAK-BOLEH-DITULIS')
        ->set('angkatan', '2026')
        ->call('saveAccount')->assertHasNoErrors()->assertRedirect(route('admin.users.index'));

    expect($staff->fresh()->name)->toBe('Nama Admin Baru')
        ->and($staff->fresh()->unit_or_prodi)->toBe('Sarpras')
        ->and($staff->fresh()->user_code)->toBe('ADMIN-SARPRAS-1')
        ->and($staff->fresh()->role)->toBe(UserRole::ADMIN_SARPRAS)
        ->and($staff->fresh()->is_active)->toBeFalse()
        ->and($staff->fresh()->password)->toBe($originalPassword)
        ->and($staff->fresh()->nim)->toBeNull()
        ->and($staff->fresh()->angkatan)->toBeNull();

    $log = $admin->activityLogs()->where('action', 'admin_account_updated')->firstOrFail();
    expect($log->old_values)->toBe(['name' => 'Nama Admin Lama', 'unit_or_prodi' => 'Sarpras lama'])
        ->and($log->new_values)->toBe(['name' => 'Nama Admin Baru', 'unit_or_prodi' => 'Sarpras']);
});

it('prevents non-super admins and account owners from editing admin accounts', function (): void {
    $student = User::factory()->create(['must_change_password' => false]);
    $staff = User::factory()->create(['role' => UserRole::ADMIN_KEMAHASISWAAN]);
    $this->actingAs($student)->withSession(['auth_version' => $student->auth_version])
        ->get(route('admin.users.edit', $staff))->assertForbidden();

    $admin = stageNineAdmin();
    $this->actingAs($admin)->withSession(['auth_version' => $admin->auth_version])
        ->get(route('admin.users.edit', $admin))->assertForbidden();
});

it('resets another account with a one-time password and requires it to change that password', function (): void {
    $admin = stageNineAdmin();
    $target = User::factory()->create(['user_code' => 'MHS-RESET', 'must_change_password' => false]);
    $originalHash = $target->password;
    $this->actingAs($admin)->withSession(['auth_version' => $admin->auth_version]);
    $component = Livewire::test(UserTable::class)->call('resetPassword', $target->id)->assertHasNoErrors();
    $temporaryPassword = $component->effects['dispatches'][1]['params']['password'] ?? null;
    expect($target->fresh()->must_change_password)->toBeTrue()
        ->and($target->fresh()->password)->not->toBe($originalHash)
        ->and($temporaryPassword)->not->toBeEmpty()
        ->and($admin->activityLogs()->where('action', 'user_password_reset')->firstOrFail()->new_values)->toBe(['must_change_password' => true]);
});

it('invalidates an already open session after an administrator resets that account password', function (): void {
    $admin = stageNineAdmin();
    $target = User::factory()->create(['must_change_password' => false, 'auth_version' => 1]);

    $this->actingAs($admin)->withSession(['auth_version' => $admin->auth_version]);
    Livewire::test(UserTable::class)->call('resetPassword', $target->id)->assertHasNoErrors();
    expect($target->fresh()->auth_version)->toBe(2);

    $this->withSession(['auth_version' => 1])->actingAs($target->fresh())
        ->get(route('profile.show'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('status');
});

it('invalidates an already open session after an administrator deactivates that account', function (): void {
    $admin = stageNineAdmin();
    $target = User::factory()->create(['must_change_password' => false, 'auth_version' => 1]);

    $this->actingAs($admin)->withSession(['auth_version' => $admin->auth_version]);
    Livewire::test(UserTable::class)->call('toggleActive', $target->id)->assertHasNoErrors();
    expect($target->fresh()->is_active)->toBeFalse()
        ->and($target->fresh()->auth_version)->toBe(2);

    $this->withSession(['auth_version' => 1])->actingAs($target->fresh())
        ->get(route('profile.show'))
        ->assertRedirect(route('login'))
        ->assertSessionHas('status');
});

it('rejects authenticated sessions that have no account version marker', function (): void {
    $user = User::factory()->create(['must_change_password' => false])->fresh();

    $this->withSession([])->actingAs($user);
    expect($user->auth_version)->toBe(1)
        ->and(session()->has('auth_version'))->toBeFalse();

    $this->get(route('profile.show'))
        ->assertRedirect(route('login'));
});

it('shows a users own profile and only that users login history', function (): void {
    $user = User::factory()->create(['must_change_password' => false]);
    $other = User::factory()->create(['must_change_password' => false]);
    $user->activityLogs()->create(['role' => 'mahasiswa', 'action' => 'login', 'created_at' => now()]);
    $other->activityLogs()->create(['role' => 'mahasiswa', 'action' => 'login', 'ip_address' => '203.0.113.2', 'created_at' => now()]);
    $this->actingAs($user)->get(route('profile.show'))->assertOk()->assertSee($user->user_code)->assertSee('Aktivitas login terakhir');
});

it('previews csv account imports and creates valid student rows without overwriting existing accounts', function (): void {
    $admin = stageNineAdmin();
    $existing = User::factory()->create(['user_code' => 'MHS-EXIST', 'name' => 'Nama Lama']);
    Storage::fake('local');
    $file = UploadedFile::fake()->createWithContent('accounts.csv', "user_code,name,unit_or_prodi\nMHS-EXIST,Nama Baru,TRPL\nMHS-NEW,Baru,TRPL\n");

    $this->actingAs($admin)->withSession(['auth_version' => $admin->auth_version]);
    Livewire::test(AdminUsersImport::class)->set('file', $file)->call('preview')
        ->assertSee('sudah ada')->assertSee('Siap diimpor')
        ->call('commit')->assertHasNoErrors();
    expect($existing->fresh()->name)->toBe('Nama Lama')
        ->and(User::query()->where('user_code', 'MHS-NEW')->value('must_change_password'))->toBeTrue()
        ->and(User::query()->where('user_code', 'MHS-NEW')->count())->toBe(1);
});

it('imports student study program, NIM, and cohort from the updated CSV template', function (): void {
    $admin = stageNineAdmin();
    $file = UploadedFile::fake()->createWithContent(
        'students.csv',
        "user_code,name,unit_or_prodi,nim,angkatan\nMHS-NEW-DATA,Mahasiswa Data,".StudyProgram::MEDIA_GRAFIS->value.",20260010,2026\n",
    );

    $this->actingAs($admin)->withSession(['auth_version' => $admin->auth_version]);
    Livewire::test(AdminUsersImport::class)->set('file', $file)->call('preview')
        ->assertSee('20260010')
        ->assertSee('Siap diimpor')
        ->call('commit')->assertHasNoErrors();

    $student = User::query()->where('user_code', 'MHS-NEW-DATA')->firstOrFail();
    expect($student->unit_or_prodi)->toBe(StudyProgram::MEDIA_GRAFIS->value)
        ->and($student->nim)->toBe('20260010')
        ->and($student->angkatan)->toBe(2026);
});

it('previews XLSX workbooks using the documented first-sheet template columns', function (): void {
    $path = tempnam(sys_get_temp_dir(), 'idn-care-xlsx-');
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('xl/sharedStrings.xml', '<?xml version="1.0"?><sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><si><t>user_code</t></si><si><t>name</t></si><si><t>unit_or_prodi</t></si><si><t>MHS-XLSX</t></si><si><t>Mahasiswa XLSX</t></si><si><t>TRPL</t></si></sst>');
    $zip->addFromString('xl/worksheets/sheet1.xml', '<?xml version="1.0"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData><row r="1"><c r="A1" t="s"><v>0</v></c><c r="B1" t="s"><v>1</v></c><c r="C1" t="s"><v>2</v></c></row><row r="2"><c r="A2" t="s"><v>3</v></c><c r="B2" t="s"><v>4</v></c><c r="C2" t="s"><v>5</v></c></row></sheetData></worksheet>');
    $zip->close();
    $reader = new ReflectionMethod(AdminUsersImport::class, 'readXlsx');
    $reader->setAccessible(true);
    expect($reader->invoke(new AdminUsersImport, $path))->toBe([
        ['user_code', 'name', 'unit_or_prodi'], ['MHS-XLSX', 'Mahasiswa XLSX', 'TRPL'],
    ]);
    $file = UploadedFile::fake()->createWithContent('accounts.xlsx', file_get_contents($path));
    $admin = stageNineAdmin();
    $this->actingAs($admin)->withSession(['auth_version' => $admin->auth_version]);
    Livewire::test(AdminUsersImport::class)
        ->set('file', $file)
        ->call('preview')
        ->assertSee('MHS-XLSX')->assertSee('Mahasiswa XLSX')->assertSee('Siap diimpor');
    unlink($path);
});

it('downloads an XLSX template that can be uploaded back for preview', function (): void {
    $admin = stageNineAdmin();
    $this->actingAs($admin)->withSession(['auth_version' => $admin->auth_version]);
    Livewire::test(AdminUsersImport::class)
        ->call('downloadTemplate')
        ->assertFileDownloaded('template-akun-idn-care.xlsx', null, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

it('archives selected reports in two steps without deleting their rows or attachments', function (): void {
    Storage::fake('local');
    $admin = stageNineAdmin();
    $reporter = User::factory()->create(['must_change_password' => false]);
    $report = stageNineReport($reporter);
    $report->attachments()->create(['file_path' => 'reports/private.jpg', 'kind' => 'before']);
    $this->actingAs($admin)->get(route('admin.archive.index'))->assertOk();
    $this->withSession(['auth_version' => $admin->auth_version]);
    Livewire::actingAs($admin)->test(AdminArchiveIndex::class)
        ->set('academicYear', '2025/2026')
        ->set('from', now()->subDay()->toDateString())
        ->set('to', now()->addDay()->toDateString())
        ->call('previewArchive')
        ->assertSet('preview.count', 1)
        ->call('archiveReports')
        ->assertSet('preview', null);
    expect(Report::query()->whereKey($report->id)->exists())->toBeFalse()
        ->and(Report::withoutGlobalScope('active')->whereKey($report->id)->value('academic_year'))->toBe('2025/2026')
        ->and($report->attachments()->count())->toBe(1)
        ->and($admin->activityLogs()->where('action', 'reports_archived')->exists())->toBeTrue();
});

it('downloads archive exports through Livewire for each supported format', function (string $format, string $contentType): void {
    $admin = stageNineAdmin();
    $reporter = User::factory()->create(['must_change_password' => false]);
    $report = stageNineReport($reporter);
    $report->forceFill(['archived_at' => now(), 'academic_year' => '2025/2026'])->save();
    $this->actingAs($admin)->withSession(['auth_version' => $admin->auth_version]);

    Livewire::actingAs($admin)->test(AdminArchiveIndex::class)
        ->call('exportArchive', $format)
        ->assertFileDownloaded(null, null, $contentType);
})->with([
    'csv' => ['csv', 'text/csv; charset=UTF-8'],
    'xlsx' => ['xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
    'pdf' => ['pdf', 'application/pdf'],
]);

it('serves archived attachments only after normal report policy authorization', function (): void {
    Storage::fake('local');
    $archivedImagePath = Storage::disk('local')->putFileAs(
        'reports',
        UploadedFile::fake()->image('archived.png'),
        'archived.jpg',
    );
    $admin = stageNineAdmin();
    $reporter = User::factory()->create(['must_change_password' => false]);
    $report = stageNineReport($reporter);
    $report->forceFill(['archived_at' => now(), 'academic_year' => '2025/2026'])->save();
    $attachment = $report->attachments()->create(['file_path' => $archivedImagePath, 'kind' => 'before']);

    $this->actingAs($admin)->withSession(['auth_version' => $admin->auth_version])->get(route('attachments.show', $attachment))->assertOk()->assertSee('data:image/png;base64,');

    $assignedUnitAdmin = User::factory()->create([
        'role' => UserRole::ADMIN_KEMAHASISWAAN,
        'must_change_password' => false,
    ])->fresh();
    $this->withSession(['auth_version' => $assignedUnitAdmin->auth_version])->actingAs($assignedUnitAdmin)
        ->get(route('attachments.show', $attachment))->assertOk()->assertSee('data:image/png;base64,');

    $wrongUnitAdmin = User::factory()->create([
        'role' => UserRole::ADMIN_SARPRAS,
        'must_change_password' => false,
    ])->fresh();
    $this->withSession(['auth_version' => $wrongUnitAdmin->auth_version])->actingAs($wrongUnitAdmin)
        ->get(route('attachments.show', $attachment))->assertForbidden();
});

it('lets students view their own archived reports and attachments without enabling changes', function (): void {
    Storage::fake('local');
    config(['app.report_key' => str_repeat('a', 64)]);
    $owner = User::factory()->create(['must_change_password' => false]);
    $other = User::factory()->create(['must_change_password' => false]);

    $archived = stageNineReport($owner);
    $archived->forceFill([
        'archived_at' => now(),
        'academic_year' => '2025/2026',
        'status' => Report::STATUS_SELESAI,
        'completed_at' => now()->subDays(8),
    ])->save();
    $archivePhotoPath = Storage::disk('local')->putFileAs(
        'reports',
        UploadedFile::fake()->image('student-archive.png'),
        'student-archive.jpg',
    );
    $attachment = $archived->attachments()->create(['file_path' => $archivePhotoPath, 'kind' => 'after']);

    $otherArchived = stageNineReport($other);
    $otherArchived->forceFill(['archived_at' => now(), 'academic_year' => '2025/2026'])->save();

    $this->actingAs($owner)->get(route('reports.index'))
        ->assertOk()->assertSee($archived->ticket_no)->assertSee('Diarsipkan');
    $this->get(route('reports.show', $archived->id))
        ->assertOk()->assertSee('Detail dan lampiran tetap tersedia untuk pemilik laporan');
    $this->get(route('attachments.show', $attachment))->assertOk()->assertSee('data:image/png;base64,');
    $this->get(route('reports.show', $otherArchived->id))->assertNotFound();

    Livewire::actingAs($owner)->test(StudentReportShow::class, ['report' => (string) $archived->id])
        ->assertDontSee('Konfirmasi hasil')
        ->call('confirm')
        ->assertForbidden();
});

it('uses the anonymous HMAC owner check for archived student report and attachment access', function (): void {
    Storage::fake('local');
    config(['app.report_key' => str_repeat('b', 64)]);
    $owner = User::factory()->create(['must_change_password' => false]);
    $other = User::factory()->create(['must_change_password' => false]);
    $type = ReportType::firstOrCreate(['code' => ReportType::PELANGGARAN], ['name' => 'Pelanggaran']);
    $category = Category::query()->create(['report_type_id' => $type->id, 'name' => 'Etika', 'is_active' => true]);
    $anonymous = Report::query()->create([
        'ticket_no' => 'IDN-ANON-ARCHIVE-1',
        'reporter_id' => null,
        'reporter_token' => Report::reporterTokenFor($owner),
        'report_type_id' => $type->id,
        'category_id' => $category->id,
        'title' => 'Laporan anonim terarsip',
        'description' => 'Uraian uji tanpa data pribadi.',
        'assigned_unit' => ReportType::UNIT_KEMAHASISWAAN,
        'is_anonymous' => true,
        'archived_at' => now(),
        'academic_year' => '2025/2026',
    ]);
    $anonymousPhotoPath = Storage::disk('local')->putFileAs(
        'reports',
        UploadedFile::fake()->image('anonymous-archive.png'),
        'anonymous-archive.jpg',
    );
    $attachment = $anonymous->attachments()->create(['file_path' => $anonymousPhotoPath, 'kind' => 'evidence']);

    $this->actingAs($owner)->get(route('reports.show', $anonymous->id))
        ->assertOk()->assertSee('Laporan anonim terarsip')->assertDontSee($owner->user_code);
    $this->get(route('attachments.show', $attachment))->assertOk()->assertSee('data:image/png;base64,');
    $this->actingAs($other)->get(route('reports.show', $anonymous->id))->assertNotFound();
    $this->get(route('attachments.show', $attachment))->assertForbidden();
});

it('prevents unit admins from accessing archive management', function (): void {
    $unitAdmin = User::factory()->create(['role' => UserRole::ADMIN_SARPRAS, 'must_change_password' => false]);
    $this->actingAs($unitAdmin)->get(route('admin.archive.index'))->assertForbidden();
});

it('creates in-app status notifications for identified reporters and none for anonymous violations', function (): void {
    $reporter = User::factory()->create(['must_change_password' => false]);
    $admin = User::factory()->create(['role' => UserRole::ADMIN_KEMAHASISWAAN, 'must_change_password' => false]);
    $report = stageNineReport($reporter);
    app(ReportStatusService::class)->update($report, $admin, ['status' => Report::STATUS_DIVERIFIKASI]);
    expect($reporter->notifications()->count())->toBe(1);
    $notification = $reporter->notifications()->firstOrFail();
    expect($notification->data)->toMatchArray(['report_id' => $report->id, 'status' => Report::STATUS_DIVERIFIKASI])
        ->and(json_encode($notification->data))->not->toContain($reporter->user_code);

    $otherStudent = User::factory()->create(['must_change_password' => false])->fresh();
    $this->withSession(['auth_version' => $otherStudent->auth_version])->actingAs($otherStudent)
        ->get(route('notifications.index'))
        ->assertOk()->assertDontSee($report->ticket_no);
    Livewire::actingAs($otherStudent)->test(NotificationsIndex::class)
        ->call('markRead', $notification->id)->assertNotFound();

    $anonymousReport = stageNineReport($reporter);
    $anonymousReport->forceFill(['reporter_id' => null, 'reporter_token' => hash('sha256', 'private-token')])->save();
    app(ReportStatusService::class)->update($anonymousReport, $admin, ['status' => Report::STATUS_DIVERIFIKASI]);
    expect($reporter->notifications()->count())->toBe(1);
});

it('sends only redacted report details to Groq and returns three structured Indonesian title suggestions', function (): void {
    Http::preventStrayRequests();
    Http::fake(['api.groq.com/*' => Http::response([
        'choices' => [['message' => ['content' => json_encode(['titles' => [
            'Lampu lorong rusak', 'Perbaikan lampu gedung', 'Lampu kampus perlu diperiksa',
        ]])]]],
    ])]);
    config(['services.groq.enabled' => true, 'services.groq.key' => 'test-key', 'services.groq.model' => 'groq-test-model']);

    $student = User::factory()->create(['role' => UserRole::MAHASISWA, 'must_change_password' => false]);
    ReportType::firstOrCreate(['code' => ReportType::FACILITAS], ['name' => 'Laporan Fasilitas']);
    Livewire::actingAs($student)->test(CreateForm::class, ['type' => ReportType::FACILITAS])
        ->set('description', 'Lampu rusak. Nama saya Budi, NIM: 2026123456, hubungi mahasiswa@example.com atau 081234567890.')
        ->call('suggestTitles')
        ->assertSet('titleSuggestions', ['Lampu lorong rusak', 'Perbaikan lampu gedung', 'Lampu kampus perlu diperiksa']);

    Http::assertSent(function (Request $request): bool {
        $userMessage = $request['messages'][1]['content'] ?? '';
        $schema = $request['response_format']['json_schema'] ?? [];

        return $request->url() === 'https://api.groq.com/openai/v1/chat/completions'
            && $request->hasHeader('Authorization', 'Bearer test-key')
            && $request['model'] === 'groq-test-model'
            && $request['messages'][0]['role'] === 'system'
            && $request['response_format']['type'] === 'json_schema'
            && $schema['strict'] === true
            && $schema['schema']['properties']['titles']['minItems'] === 3
            && $schema['schema']['properties']['titles']['maxItems'] === 3
            && str_contains($userMessage, 'Jenis laporan: fasilitas')
            && str_contains($userMessage, '[email disamarkan]')
            && str_contains($userMessage, '[nomor telepon disamarkan]')
            && str_contains($userMessage, '[data pribadi disamarkan]')
            && ! str_contains($userMessage, 'reporter_id')
            && ! str_contains($userMessage, 'Budi')
            && ! str_contains($userMessage, '2026123456')
            && ! str_contains($userMessage, 'mahasiswa@example.com');
    });
});

it('rejects invalid, incomplete, duplicate, and overlong Groq titles', function (array $titles): void {
    Http::preventStrayRequests();
    Http::fake(['api.groq.com/*' => Http::response([
        'choices' => [['message' => ['content' => json_encode(['titles' => $titles])]]],
    ])]);
    config(['services.groq.enabled' => true, 'services.groq.key' => 'test-key']);

    expect(app(GroqTitleSuggestionService::class)->suggest('aspirasi', 'Deskripsi contoh cukup panjang.'))->toBe([]);
})->with([
    'incomplete' => [['Judul satu', 'Judul dua']],
    'duplicate' => [['Judul sama', 'Judul sama', 'Judul lain']],
    'empty' => [['Judul satu', '  ', 'Judul tiga']],
    'overlong' => [[str_repeat('a', 151), 'Judul dua', 'Judul tiga']],
]);

it('rejects malformed Groq JSON output', function (): void {
    Http::preventStrayRequests();
    Http::fake(['api.groq.com/*' => Http::response([
        'choices' => [['message' => ['content' => '{not-json']]],
    ])]);
    config(['services.groq.enabled' => true, 'services.groq.key' => 'test-key']);

    expect(app(GroqTitleSuggestionService::class)->suggest('aspirasi', 'Deskripsi contoh cukup panjang.'))->toBe([]);
});

it('validates the report type and description before calling Groq', function (): void {
    Http::preventStrayRequests();
    Http::fake();
    config(['services.groq.enabled' => true, 'services.groq.key' => 'test-key']);
    $student = User::factory()->create(['role' => UserRole::MAHASISWA, 'must_change_password' => false]);
    ReportType::firstOrCreate(['code' => ReportType::ASPIRASI], ['name' => 'Aspirasi']);

    Livewire::actingAs($student)->test(CreateForm::class, ['type' => ReportType::ASPIRASI])
        ->set('description', 'pendek')
        ->call('suggestTitles')
        ->assertHasErrors('description');

    Http::assertNothingSent();
});

it('throttles title suggestion requests at ten per minute', function (): void {
    Http::preventStrayRequests();
    Http::fake(['api.groq.com/*' => Http::response([
        'choices' => [['message' => ['content' => json_encode(['titles' => ['Judul satu', 'Judul dua', 'Judul tiga']])]]],
    ])]);
    config(['services.groq.enabled' => true, 'services.groq.key' => 'test-key']);
    $student = User::factory()->create(['role' => UserRole::MAHASISWA, 'must_change_password' => false]);
    ReportType::firstOrCreate(['code' => ReportType::ASPIRASI], ['name' => 'Aspirasi']);

    for ($attempt = 0; $attempt < 10; $attempt++) {
        Livewire::actingAs($student)->test(CreateForm::class, ['type' => ReportType::ASPIRASI])
            ->set('description', 'Usulan peningkatan fasilitas kampus.')
            ->call('suggestTitles');
    }

    Livewire::actingAs($student)->test(CreateForm::class, ['type' => ReportType::ASPIRASI])
        ->set('description', 'Usulan peningkatan fasilitas kampus.')
        ->call('suggestTitles')
        ->assertSee('Kuota layanan saran judul sedang terbatas. Coba lagi nanti atau tulis judul secara manual.');

    Http::assertSentCount(10);
});

it('returns an empty fallback when Groq times out', function (): void {
    Http::preventStrayRequests();
    Http::fake(['api.groq.com/*' => Http::failedConnection('cURL error 28: Operation timed out')]);
    config(['services.groq.enabled' => true, 'services.groq.key' => 'test-key']);

    expect(app(GroqTitleSuggestionService::class)->suggest('aspirasi', 'Deskripsi dummy tanpa data pribadi.'))->toBe([]);
});

it('shows an Indonesian timeout fallback for the title suggestion request', function (): void {
    Http::preventStrayRequests();
    Http::fake(['api.groq.com/*' => Http::failedConnection('cURL error 28: Operation timed out')]);
    config(['services.groq.enabled' => true, 'services.groq.key' => 'test-key']);
    $student = User::factory()->create(['role' => UserRole::MAHASISWA, 'must_change_password' => false]);
    ReportType::firstOrCreate(['code' => ReportType::ASPIRASI], ['name' => 'Aspirasi']);

    Livewire::actingAs($student)->test(CreateForm::class, ['type' => ReportType::ASPIRASI])
        ->set('description', 'Deskripsi dummy yang cukup panjang.')
        ->call('suggestTitles')
        ->assertSet('titleSuggestions', [])
        ->assertSee('Layanan saran judul merespons terlalu lama. Kamu tetap bisa menulis judul dan mengirim laporan.');
});

it('returns Indonesian fallbacks for Groq authentication, quota, and provider errors', function (int $status, string $message): void {
    Http::preventStrayRequests();
    Http::fake(['api.groq.com/*' => Http::response(['error' => ['message' => 'sensitive provider details']], $status)]);
    config(['services.groq.enabled' => true, 'services.groq.key' => 'test-key']);
    $student = User::factory()->create(['role' => UserRole::MAHASISWA, 'must_change_password' => false]);
    ReportType::firstOrCreate(['code' => ReportType::ASPIRASI], ['name' => 'Aspirasi']);

    Livewire::actingAs($student)->test(CreateForm::class, ['type' => ReportType::ASPIRASI])
        ->set('description', 'Deskripsi dummy yang cukup panjang.')
        ->call('suggestTitles')
        ->assertSet('titleSuggestions', [])
        ->assertSee($message);
})->with([
    'unauthorized' => [401, 'Layanan saran judul belum dapat diakses. Silakan tulis judul secara manual; administrator perlu memeriksa konfigurasi layanan.'],
    'forbidden' => [403, 'Layanan saran judul belum dapat diakses. Silakan tulis judul secara manual; administrator perlu memeriksa konfigurasi layanan.'],
    'quota' => [429, 'Kuota layanan saran judul sedang terbatas. Coba lagi nanti atau tulis judul secara manual.'],
    'provider error' => [502, 'Saran AI tidak tersedia saat ini. Kamu tetap bisa menulis judul dan mengirim laporan.'],
]);

it('provides a safe Groq Artisan diagnostic without printing the configured key', function (): void {
    Http::preventStrayRequests();
    Http::fake(['api.groq.com/*' => Http::response([], 503)]);
    config(['services.groq.enabled' => true, 'services.groq.key' => 'diagnostic-test-key']);

    $exitCode = Artisan::call('groq:diagnose-titles');
    $output = Artisan::output();

    expect($exitCode)->toBe(1)
        ->and($output)->toContain('HTTP: 503 | kategori: http_error')
        ->and($output)->toContain('jumlah_judul: 0')
        ->and($output)->not->toContain('diagnostic-test-key');
});

it('rejects unsafe Groq diagnostic timeout values without making a provider request', function (): void {
    Http::preventStrayRequests();
    Http::fake();

    expect(Artisan::call('groq:diagnose-titles', ['--timeout' => '61']))->toBe(1)
        ->and(Artisan::output())->toContain('antara 1 dan 60 detik');

    Http::assertNothingSent();
});

it('does not call Groq when title suggestions are disabled or the key is unavailable', function (bool $enabled, ?string $key): void {
    Http::preventStrayRequests();
    Http::fake();
    config(['services.groq.enabled' => $enabled, 'services.groq.key' => $key]);

    expect(app(GroqTitleSuggestionService::class)->suggest('aspirasi', 'Deskripsi dummy tanpa data pribadi.'))->toBe([]);

    Http::assertNothingSent();
})->with([
    'disabled' => [false, 'test-key'],
    'key unavailable' => [true, null],
]);

it('logs only sanitized Groq connection diagnostics', function (): void {
    Http::preventStrayRequests();
    Http::fake(['api.groq.com/*' => Http::failedConnection(
        'cURL error 6: Could not resolve host: api.groq.com; sensitive prompt and API key must not be logged',
    )]);
    Log::spy();
    config(['services.groq.enabled' => true, 'services.groq.key' => 'test-key']);

    expect(app(GroqTitleSuggestionService::class)
        ->suggest('aspirasi', 'Dummy description that must not appear in logs.'))->toBe([]);

    Log::shouldHaveReceived('warning')->once()->withArgs(function (string $message, array $context): bool {
        return $message === 'Groq title suggestions could not connect.'
            && $context['failure_type'] === 'dns'
            && $context['curl_code'] === 6
            && ! str_contains(json_encode($context), 'sensitive prompt')
            && ! str_contains(json_encode($context), 'test-key');
    });
});

it('renders a students report list and detail with the ticket_no database column', function (): void {
    config(['app.report_key' => str_repeat('t', 64)]);
    $student = User::factory()->create(['role' => UserRole::MAHASISWA, 'must_change_password' => false]);
    $report = stageNineReport($student);

    $this->withSession(['auth_version' => $student->auth_version])->actingAs($student)
        ->get(route('reports.index'))
        ->assertOk()
        ->assertSee($report->ticket_no);

    $this->get(route('reports.show', $report))
        ->assertOk()
        ->assertSee($report->ticket_no);
});

it('keeps the students report list available when the anonymous ownership key needs restoration', function (): void {
    config(['app.report_key' => null]);
    $student = User::factory()->create(['role' => UserRole::MAHASISWA, 'must_change_password' => false]);
    $report = stageNineReport($student);

    $this->withSession(['auth_version' => $student->auth_version])->actingAs($student)
        ->get(route('reports.index'))
        ->assertOk()
        ->assertSee($report->ticket_no);
});
