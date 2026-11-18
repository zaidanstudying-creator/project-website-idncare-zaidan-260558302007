<?php

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Location;
use App\Models\Report;
use App\Models\ReportType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createMasterDataTypes(): array
{
    return collect([
        ReportType::FACILITAS => 'Laporan Fasilitas',
        ReportType::ASPIRASI => 'Aspirasi',
        ReportType::PELANGGARAN => 'Dugaan Pelanggaran',
    ])->map(fn (string $name, string $code): ReportType => ReportType::create([
        'code' => $code,
        'name' => $name,
    ]))->all();
}

it('requires authentication and redirects students away from master data', function (): void {
    $this->get(route('admin.categories.index'))->assertRedirect(route('login'));
    $this->get(route('admin.locations.index'))->assertRedirect(route('login'));

    $student = User::factory()->create(['must_change_password' => false]);

    $this->actingAs($student)->get(route('admin.categories.index'))->assertForbidden();
    $this->get(route('admin.locations.index'))->assertForbidden();
});

it('requires a password change before an account can use master data', function (): void {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN_SARPRAS,
        'must_change_password' => true,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.categories.index'))
        ->assertRedirect(route('password.change'));
});

it('limits category management to the report types assigned to each unit', function (): void {
    $types = createMasterDataTypes();
    $facilityCategory = Category::create([
        'report_type_id' => $types[ReportType::FACILITAS]->id,
        'name' => 'Listrik',
        'is_active' => true,
    ]);
    $aspirationCategory = Category::create([
        'report_type_id' => $types[ReportType::ASPIRASI]->id,
        'name' => 'Akademik',
        'is_active' => true,
    ]);
    $violationCategory = Category::create([
        'report_type_id' => $types[ReportType::PELANGGARAN]->id,
        'name' => 'Keamanan',
        'is_active' => true,
    ]);
    $sarpras = User::factory()->create([
        'role' => UserRole::ADMIN_SARPRAS,
        'must_change_password' => false,
    ]);
    $kemahasiswaan = User::factory()->create([
        'role' => UserRole::ADMIN_KEMAHASISWAAN,
        'must_change_password' => false,
    ]);

    $this->actingAs($sarpras)->get(route('admin.categories.index'))
        ->assertOk()
        ->assertSee('Listrik')
        ->assertDontSee('Akademik')
        ->assertDontSee('Keamanan');
    $this->actingAs($kemahasiswaan)->get(route('admin.categories.index'))
        ->assertOk()
        ->assertDontSee('Listrik')
        ->assertSee('Akademik')
        ->assertSee('Keamanan');

    $this->actingAs($sarpras)->put(route('admin.categories.update', $aspirationCategory), [
        'name' => 'Akademik diubah',
        'is_active' => true,
    ])->assertForbidden();

    $this->actingAs($kemahasiswaan)->put(route('admin.categories.update', $facilityCategory), [
        'name' => 'Listrik diubah',
        'is_active' => true,
    ])->assertForbidden();

    expect($violationCategory->fresh()->name)->toBe('Keamanan');
});

it('lets authorized staff create categories only for their assigned unit', function (): void {
    $types = createMasterDataTypes();
    $sarpras = User::factory()->create([
        'role' => UserRole::ADMIN_SARPRAS,
        'must_change_password' => false,
    ]);
    $kemahasiswaan = User::factory()->create([
        'role' => UserRole::ADMIN_KEMAHASISWAAN,
        'must_change_password' => false,
    ]);
    $superAdmin = User::factory()->create([
        'role' => UserRole::SUPER_ADMIN,
        'must_change_password' => false,
    ]);

    $this->actingAs($sarpras)->post(route('admin.categories.store'), [
        'report_type_id' => $types[ReportType::FACILITAS]->id,
        'name' => 'Kebersihan gedung',
    ])->assertRedirect(route('admin.categories.index'));

    $this->actingAs($sarpras)->post(route('admin.categories.store'), [
        'report_type_id' => $types[ReportType::ASPIRASI]->id,
        'name' => 'Aspirasi umum',
    ])->assertForbidden();

    $this->actingAs($kemahasiswaan)->post(route('admin.categories.store'), [
        'report_type_id' => $types[ReportType::PELANGGARAN]->id,
        'name' => 'Etika',
    ])->assertRedirect(route('admin.categories.index'));

    $this->actingAs($superAdmin)->post(route('admin.categories.store'), [
        'report_type_id' => $types[ReportType::FACILITAS]->id,
        'name' => 'Konstruksi',
    ])->assertRedirect(route('admin.categories.index'));

    $this->assertDatabaseHas('categories', [
        'report_type_id' => $types[ReportType::FACILITAS]->id,
        'name' => 'Kebersihan gedung',
    ]);
    $this->assertDatabaseHas('categories', [
        'report_type_id' => $types[ReportType::PELANGGARAN]->id,
        'name' => 'Etika',
    ]);
});

it('allows both unit admins and super admin to manage locations', function (): void {
    $sarpras = User::factory()->create([
        'role' => UserRole::ADMIN_SARPRAS,
        'must_change_password' => false,
    ]);
    $kemahasiswaan = User::factory()->create([
        'role' => UserRole::ADMIN_KEMAHASISWAAN,
        'must_change_password' => false,
    ]);
    $superAdmin = User::factory()->create([
        'role' => UserRole::SUPER_ADMIN,
        'must_change_password' => false,
    ]);

    $this->actingAs($sarpras)->post(route('admin.locations.store'), [
        'name' => 'Gedung Baru',
    ])->assertRedirect(route('admin.locations.index'));

    $location = Location::query()->where('name', 'Gedung Baru')->firstOrFail();

    $this->actingAs($kemahasiswaan)->put(route('admin.locations.update', $location), [
        'name' => 'Gedung Baru Timur',
        'is_active' => true,
    ])->assertRedirect(route('admin.locations.index'));

    $this->actingAs($superAdmin)->get(route('admin.locations.index'))->assertOk();
    $this->actingAs($kemahasiswaan)->get(route('admin.locations.index'))->assertOk();

    $this->actingAs($superAdmin)->put(route('admin.locations.update', $location), [
        'name' => 'Gedung Baru Utama',
        'is_active' => false,
    ])->assertRedirect(route('admin.locations.index'));

    expect($location->fresh()->name)->toBe('Gedung Baru Utama')
        ->and($location->fresh()->is_active)->toBeFalse();
});

it('deactivates used categories and locations without deleting referenced master data', function (): void {
    $type = ReportType::create(['code' => ReportType::FACILITAS, 'name' => 'Laporan Fasilitas']);
    $category = Category::create([
        'report_type_id' => $type->id,
        'name' => 'Plumbing',
        'is_active' => true,
    ]);
    $location = Location::create(['name' => 'Gedung Lama', 'is_active' => true]);
    $reporter = User::factory()->create();
    $report = Report::create([
        'ticket_no' => 'TEST-MASTER-0001',
        'reporter_id' => $reporter->id,
        'report_type_id' => $type->id,
        'category_id' => $category->id,
        'location_id' => $location->id,
        'title' => 'Kran rusak',
        'description' => 'Contoh laporan yang memakai master data.',
        'assigned_unit' => ReportType::UNIT_SARPRAS,
    ]);
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN_SARPRAS,
        'must_change_password' => false,
    ]);

    $this->actingAs($admin)->put(route('admin.categories.update', $category), [
        'name' => 'Plumbing',
        'is_active' => false,
    ])->assertRedirect(route('admin.categories.index'));
    $this->put(route('admin.locations.update', $location), [
        'name' => 'Gedung Lama',
        'is_active' => false,
    ])->assertRedirect(route('admin.locations.index'));

    expect($category->fresh()->is_active)->toBeFalse()
        ->and($location->fresh()->is_active)->toBeFalse()
        ->and($report->fresh()->category_id)->toBe($category->id)
        ->and($report->fresh()->location_id)->toBe($location->id)
        ->and(Category::query()->whereKey($category->id)->exists())->toBeTrue()
        ->and(Location::query()->whereKey($location->id)->exists())->toBeTrue();
});

it('validates category and location fields before storing', function (): void {
    $types = createMasterDataTypes();
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN_SARPRAS,
        'must_change_password' => false,
    ]);

    $this->actingAs($admin)->post(route('admin.categories.store'), [
        'report_type_id' => $types[ReportType::FACILITAS]->id,
        'name' => str_repeat('a', 151),
    ])->assertSessionHasErrors('name');

    $this->post(route('admin.locations.store'), ['name' => ''])
        ->assertSessionHasErrors('name');
});
