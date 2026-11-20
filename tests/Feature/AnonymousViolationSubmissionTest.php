<?php

use App\Enums\UserRole;
use App\Livewire\Pages\Reports\CreateForm;
use App\Models\Category;
use App\Models\Location;
use App\Models\Report;
use App\Models\ReportType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('submits an anonymous violation while keeping visibility server controlled', function (): void {
    config(['app.report_key' => str_repeat('a', 64)]);

    $student = User::factory()->create([
        'role' => UserRole::MAHASISWA,
        'must_change_password' => false,
    ]);
    $type = ReportType::query()->create(['code' => ReportType::PELANGGARAN, 'name' => 'Pelanggaran']);
    $category = Category::query()->create([
        'report_type_id' => $type->id,
        'name' => 'Etika',
        'is_active' => true,
    ]);
    $location = Location::query()->create(['name' => 'Gedung Utama', 'is_active' => true]);

    Livewire::actingAs($student)
        ->test(CreateForm::class, ['type' => $type->code])
        ->set('title', 'Dugaan pelanggaran anonim')
        ->set('description', 'Uraian sintetis untuk uji laporan anonim.')
        ->set('categoryId', (string) $category->id)
        ->set('locationId', (string) $location->id)
        ->set('incidentDate', now()->toDateString())
        ->set('isAnonymous', true)
        ->call('submit')
        ->assertHasNoErrors();

    $report = Report::query()->sole();

    expect($report->reporter_id)->toBeNull()
        ->and($report->reporter_token)->toBe(Report::reporterTokenFor($student))
        ->and($report->requested_visibility)->toBe(Report::VISIBILITY_RAHASIA)
        ->and($report->approved_visibility)->toBe(Report::VISIBILITY_RAHASIA);
});
