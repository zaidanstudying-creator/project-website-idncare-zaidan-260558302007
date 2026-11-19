<?php

use App\Enums\UserRole;
use App\Livewire\Pages\Admin\Categories\Index as AdminCategoriesIndex;
use App\Livewire\Pages\Admin\Locations\Index as AdminLocationsIndex;
use App\Models\Category;
use App\Models\Location;
use App\Models\Report;
use App\Models\ReportType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

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

it('renders the location management page for authorized staff', function (): void {
    $admin = User::factory()->create([
        'role' => UserRole::ADMIN_SARPRAS,
        'must_change_password' => false,
    ]);

    $this->actingAs($admin)
        ->withSession(['auth_version' => $admin->auth_version])
        ->get(route('admin.locations.index'))
        ->assertOk()
        ->assertSee('Tambah lokasi')
        ->assertSee('Daftar lokasi');
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

    Livewire::actingAs($sarpras)->test(AdminCategoriesIndex::class)
        ->assertSee('Listrik')
        ->assertDontSee('Akademik')
        ->assertDontSee('Keamanan');
    Livewire::actingAs($kemahasiswaan)->test(AdminCategoriesIndex::class)
        ->assertDontSee('Listrik')
        ->assertSee('Akademik')
        ->assertSee('Keamanan');

    Livewire::actingAs($sarpras)->test(AdminCategoriesIndex::class)
        ->call('updateCategory', $aspirationCategory->id)
        ->assertForbidden();

    Livewire::actingAs($kemahasiswaan)->test(AdminCategoriesIndex::class)
        ->call('updateCategory', $facilityCategory->id)
        ->assertForbidden();

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

    Livewire::actingAs($sarpras)->test(AdminCategoriesIndex::class)
        ->set('newReportTypeId', (string) $types[ReportType::FACILITAS]->id)
        ->set('newName', 'Kebersihan gedung')
        ->call('createCategory')
        ->assertHasNoErrors();

    Livewire::actingAs($sarpras)->test(AdminCategoriesIndex::class)
        ->set('newReportTypeId', (string) $types[ReportType::ASPIRASI]->id)
        ->set('newName', 'Aspirasi umum')
        ->call('createCategory')
        ->assertForbidden();

    Livewire::actingAs($kemahasiswaan)->test(AdminCategoriesIndex::class)
        ->set('newReportTypeId', (string) $types[ReportType::PELANGGARAN]->id)
        ->set('newName', 'Etika')
        ->call('createCategory')
        ->assertHasNoErrors();

    Livewire::actingAs($superAdmin)->test(AdminCategoriesIndex::class)
        ->set('newReportTypeId', (string) $types[ReportType::FACILITAS]->id)
        ->set('newName', 'Konstruksi')
        ->call('createCategory')
        ->assertHasNoErrors();

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

    Livewire::actingAs($sarpras)->test(AdminLocationsIndex::class)
        ->set('newName', 'Gedung Baru')
        ->call('createLocation')
        ->assertHasNoErrors();

    $location = Location::query()->where('name', 'Gedung Baru')->firstOrFail();

    Livewire::actingAs($kemahasiswaan)->test(AdminLocationsIndex::class)
        ->set("locationEdits.{$location->id}.name", 'Gedung Baru Timur')
        ->set("locationEdits.{$location->id}.is_active", true)
        ->call('updateLocation', $location->id)
        ->assertHasNoErrors();

    $this->actingAs($superAdmin)->get(route('admin.locations.index'))->assertOk();
    $this->actingAs($kemahasiswaan)->get(route('admin.locations.index'))->assertOk();

    Livewire::actingAs($superAdmin)->test(AdminLocationsIndex::class)
        ->set("locationEdits.{$location->id}.name", 'Gedung Baru Utama')
        ->set("locationEdits.{$location->id}.is_active", false)
        ->call('updateLocation', $location->id)
        ->assertHasNoErrors();

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

    Livewire::actingAs($admin)->test(AdminCategoriesIndex::class)
        ->set("categoryEdits.{$category->id}.name", 'Plumbing')
        ->set("categoryEdits.{$category->id}.is_active", false)
        ->call('updateCategory', $category->id)
        ->assertHasNoErrors();
    Livewire::actingAs($admin)->test(AdminLocationsIndex::class)
        ->set("locationEdits.{$location->id}.name", 'Gedung Lama')
        ->set("locationEdits.{$location->id}.is_active", false)
        ->call('updateLocation', $location->id)
        ->assertHasNoErrors();

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

    Livewire::actingAs($admin)->test(AdminCategoriesIndex::class)
        ->set('newReportTypeId', (string) $types[ReportType::FACILITAS]->id)
        ->set('newName', str_repeat('a', 151))
        ->call('createCategory')
        ->assertHasErrors('newName');

    Livewire::actingAs($admin)->test(AdminLocationsIndex::class)
        ->set('newName', '')
        ->call('createLocation')
        ->assertHasErrors('newName');
});
