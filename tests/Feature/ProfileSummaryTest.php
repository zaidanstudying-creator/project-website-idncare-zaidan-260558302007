<?php

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Report;
use App\Models\ReportType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function profileReport(User $reporter, string $typeCode, array $attributes = []): Report
{
    $type = ReportType::query()->firstOrCreate(
        ['code' => $typeCode],
        ['name' => ucfirst($typeCode)],
    );
    $category = Category::query()->firstOrCreate(
        ['report_type_id' => $type->id, 'name' => 'Profil '.$typeCode],
        ['is_active' => true],
    );

    return Report::query()->create([
        'ticket_no' => 'PROFIL-'.strtoupper($typeCode).'-'.fake()->unique()->numerify('####'),
        'reporter_id' => $reporter->id,
        'report_type_id' => $type->id,
        'category_id' => $category->id,
        'title' => 'Laporan untuk ringkasan profil',
        'description' => 'Deskripsi pengujian.',
        'assigned_unit' => $type->assignedUnit(),
        ...$attributes,
    ]);
}

it('shows only the students own report counts in the profile summary', function (): void {
    $student = User::factory()->create([
        'must_change_password' => false,
        'unit_or_prodi' => 'Teknologi rekayasa perangkat lunak',
        'nim' => '20260001',
        'angkatan' => 2026,
    ]);
    $otherStudent = User::factory()->create(['must_change_password' => false]);
    profileReport($student, ReportType::FACILITAS, ['status' => Report::STATUS_SELESAI]);
    profileReport($student, ReportType::ASPIRASI, ['status' => Report::STATUS_SELESAI, 'confirmed_at' => now()]);
    profileReport($student, ReportType::ASPIRASI, ['archived_at' => now(), 'academic_year' => '2025/2026']);
    profileReport($otherStudent, ReportType::ASPIRASI);

    $response = $this->actingAs($student)->get(route('profile.show'))
        ->assertOk()
        ->assertSee('Laporan saya')
        ->assertSee('Program studi')
        ->assertSee('Teknologi rekayasa perangkat lunak')
        ->assertSee('20260001')
        ->assertSee('2026')
        ->assertSee('Menunggu konfirmasimu')
        ->assertSee('Terkonfirmasi')
        ->assertSee('Laporan anonim tetap bisa kamu lihat sendiri');

    expect(collect($response->viewData('profileSummary')['items'])->pluck('value')->all())->toBe([3, 1, 1]);
});

it('shows unit-scoped handling figures for staff profiles', function (): void {
    $sarpras = User::factory()->create(['role' => UserRole::ADMIN_SARPRAS, 'must_change_password' => false]);
    $kemahasiswaan = User::factory()->create(['role' => UserRole::ADMIN_KEMAHASISWAAN, 'must_change_password' => false]);
    $student = User::factory()->create(['must_change_password' => false]);
    $completedFacility = profileReport($student, ReportType::FACILITAS, [
        'status' => Report::STATUS_SELESAI,
        'completed_at' => now(),
    ]);
    $completedFacility->forceFill(['created_at' => now()->subDays(2)])->save();
    profileReport($student, ReportType::ASPIRASI);
    profileReport($student, ReportType::PELANGGARAN);

    expect($completedFacility->created_at->diffInSeconds($completedFacility->completed_at))->toBeGreaterThan(1);

    $sarprasResponse = $this->actingAs($sarpras)->get(route('profile.show'))
        ->assertOk()
        ->assertSee('Laporan ditangani')
        ->assertSee('Rata-rata penyelesaian (hari)')
        ->assertSee('2,0');
    expect(collect($sarprasResponse->viewData('profileSummary')['items'])->pluck('value')->all())->toBe([1, 1, 2.0]);

    $kemahasiswaanResponse = $this->actingAs($kemahasiswaan)->get(route('profile.show'))
        ->assertOk()
        ->assertSee('Aspirasi ditangani')
        ->assertSee('Pelanggaran ditangani')
        ->assertSee('Identitas pelapor anonim pada laporan pelanggaran tidak dapat dibuka siapa pun.');
    expect(collect($kemahasiswaanResponse->viewData('profileSummary')['items'])->pluck('value')->all())->toBe([1, 1, null]);
});

it('includes archived records in the super admins profile totals without exposing reporter details', function (): void {
    $superAdmin = User::factory()->create(['role' => UserRole::SUPER_ADMIN, 'must_change_password' => false]);
    $student = User::factory()->create(['must_change_password' => false]);
    $report = profileReport($student, ReportType::PELANGGARAN, [
        'reporter_id' => null,
        'reporter_token' => str_repeat('a', 64),
        'is_anonymous' => true,
        'requested_visibility' => Report::VISIBILITY_RAHASIA,
        'approved_visibility' => Report::VISIBILITY_RAHASIA,
        'academic_year' => '2025/2026',
        'archived_at' => now(),
    ]);

    $response = $this->actingAs($superAdmin)->get(route('profile.show'))
        ->assertOk()
        ->assertSee('Akun dikelola')
        ->assertSee('Tahun ajaran terarsip')
        ->assertSee('Identitas pelapor anonim pada laporan pelanggaran tidak dapat dibuka siapa pun, termasuk Super Admin.')
        ->assertDontSee($report->reporter_token);

    expect(collect($response->viewData('profileSummary')['items'])->pluck('value')->all())->toBe([2, 1, 1]);
});

it('does not allow profile requests to mutate names, IDs, units, roles, or active state', function (): void {
    $user = User::factory()->create([
        'role' => UserRole::MAHASISWA,
        'name' => 'Nama Resmi',
        'user_code' => 'MHS-99881',
        'unit_or_prodi' => 'TRPL',
        'must_change_password' => false,
    ]);

    $this->actingAs($user)->put(route('profile.show'), [
        'name' => 'Nama Diubah',
        'user_code' => 'SUPER-HACK',
        'unit_or_prodi' => 'Unit Lain',
        'role' => UserRole::SUPER_ADMIN->value,
        'is_active' => false,
    ])->assertMethodNotAllowed();

    expect($user->fresh()->name)->toBe('Nama Resmi')
        ->and($user->fresh()->user_code)->toBe('MHS-99881')
        ->and($user->fresh()->unit_or_prodi)->toBe('TRPL')
        ->and($user->fresh()->role)->toBe(UserRole::MAHASISWA)
        ->and($user->fresh()->is_active)->toBeTrue();
});
