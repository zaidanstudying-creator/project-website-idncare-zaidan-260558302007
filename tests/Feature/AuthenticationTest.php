<?php

use App\Enums\UserRole;
use App\Livewire\Components\Auth\Logout;
use App\Livewire\Pages\Auth\Login;
use App\Livewire\Pages\Auth\PasswordChange;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Report;
use App\Models\ReportType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('requires guests to log in before opening a protected page', function (): void {
    $this->get('/dashboard')->assertRedirect(route('login'));
});

it('logs in active accounts by user code and regenerates the session', function (): void {
    $user = User::factory()->create([
        'user_code' => 'MHS-LOGIN-01',
        'password' => 'temporary-password',
        'must_change_password' => false,
    ]);
    $oldSessionId = session()->getId();

    Livewire::test(Login::class)
        ->set('userCode', $user->user_code)
        ->set('password', 'temporary-password')
        ->call('authenticate')
        ->assertRedirect(route('dashboard'));

    expect(auth()->id())->toBe($user->id)
        ->and(session()->getId())->not->toBe($oldSessionId)
        ->and(ActivityLog::query()->where('action', 'login')->sole()->user_id)->toBe($user->id);
});

it('limits idle sessions to thirty minutes', function (): void {
    expect(config('session.lifetime'))->toBeLessThanOrEqual(30);
});

it('rejects invalid credentials and inactive accounts with the same message', function (): void {
    $active = User::factory()->create([
        'user_code' => 'MHS-ACTIVE-01',
        'password' => 'correct-password',
    ]);
    User::factory()->create([
        'user_code' => 'MHS-INACTIVE-01',
        'password' => 'correct-password',
        'is_active' => false,
    ]);

    Livewire::test(Login::class)
        ->set('userCode', $active->user_code)
        ->set('password', 'wrong-password')
        ->call('authenticate')
        ->assertHasErrors('userCode');
    expect(auth()->check())->toBeFalse();

    Livewire::test(Login::class)
        ->set('userCode', 'MHS-INACTIVE-01')
        ->set('password', 'correct-password')
        ->call('authenticate')
        ->assertHasErrors('userCode');
    expect(auth()->check())->toBeFalse();
});

it('forces a first-login password change before protected pages', function (): void {
    $user = User::factory()->create([
        'user_code' => 'MHS-FIRST-01',
        'password' => 'temporary-password',
        'must_change_password' => true,
    ]);

    Livewire::test(Login::class)
        ->set('userCode', $user->user_code)
        ->set('password', 'temporary-password')
        ->call('authenticate')
        ->assertRedirect(route('password.change'));

    $this->get('/dashboard')->assertRedirect(route('password.change'));
    $this->get(route('password.change'))->assertOk();

    Livewire::actingAs($user)->test(PasswordChange::class)
        ->set('current_password', 'temporary-password')
        ->set('password', 'a-new-secure-password')
        ->set('password_confirmation', 'a-new-secure-password')
        ->call('savePassword')
        ->assertRedirect(route('dashboard'));

    expect($user->fresh()->must_change_password)->toBeFalse()
        ->and(Hash::check('a-new-secure-password', $user->fresh()->password))->toBeTrue()
        ->and(ActivityLog::query()->where('action', 'password_changed')->sole()->new_values)->toBe(['must_change_password' => false]);

});

it('requires at least eight characters without adding complexity rules absent from the PRD', function (): void {
    $user = User::factory()->create([
        'password' => 'temporary-password',
        'must_change_password' => false,
    ]);

    Livewire::actingAs($user)->test(PasswordChange::class)
        ->set('current_password', 'temporary-password')
        ->set('password', 'short7!')
        ->set('password_confirmation', 'short7!')
        ->call('savePassword')
        ->assertHasErrors('password');

    Livewire::actingAs($user)->test(PasswordChange::class)
        ->set('current_password', 'temporary-password')
        ->set('password', 'abcdefgh')
        ->set('password_confirmation', 'abcdefgh')
        ->call('savePassword')
        ->assertRedirect(route('dashboard'));
});

it('invalidates the session and rotates the csrf token on logout', function (): void {
    $user = User::factory()->create(['must_change_password' => false]);
    $oldToken = session()->token();

    Livewire::actingAs($user)->test(Logout::class)
        ->call('logout')
        ->assertRedirect(route('login'));

    expect(auth()->check())->toBeFalse()
        ->and(session()->token())->not->toBe($oldToken)
        ->and(ActivityLog::query()->where('action', 'logout')->sole()->user_id)->toBe($user->id);
});

it('restricts role landing pages to the matching role', function (): void {
    $student = User::factory()->create([
        'role' => UserRole::MAHASISWA,
        'must_change_password' => false,
    ]);

    $this->actingAs($student)
        ->get('/dashboard/admin-sarpras')
        ->assertForbidden();

    $sarprasAdmin = User::factory()->create([
        'role' => UserRole::ADMIN_SARPRAS,
        'must_change_password' => false,
    ]);

    $this->actingAs($sarprasAdmin)
        ->get('/dashboard/admin-sarpras')
        ->assertOk();

    $this->get('/dashboard/admin-kemahasiswaan')->assertForbidden();
});

it('authorizes report access by ownership, public visibility, and assigned unit', function (): void {
    config(['app.report_key' => str_repeat('c', 64)]);
    $student = User::factory()->create();
    $otherStudent = User::factory()->create();
    $sarprasAdmin = User::factory()->create(['role' => UserRole::ADMIN_SARPRAS]);
    $kemahasiswaanAdmin = User::factory()->create(['role' => UserRole::ADMIN_KEMAHASISWAAN]);
    $superAdmin = User::factory()->create(['role' => UserRole::SUPER_ADMIN]);
    $facility = ReportType::create(['code' => 'fasilitas', 'name' => 'Fasilitas']);
    $aspiration = ReportType::create(['code' => 'aspirasi', 'name' => 'Aspirasi']);

    $owned = makePolicyReport($facility, $student, ['reporter_id' => $student->id]);
    $public = makePolicyReport($aspiration, $otherStudent, [
        'reporter_id' => $otherStudent->id,
        'requested_visibility' => 'umum',
        'visibility_status' => 'disetujui',
        'approved_visibility' => 'umum',
    ]);
    $private = makePolicyReport($aspiration, $otherStudent, ['reporter_id' => $otherStudent->id]);

    expect(Gate::forUser($student)->allows('view', $owned))->toBeTrue()
        ->and(Gate::forUser($student)->allows('view', $public))->toBeTrue()
        ->and(Gate::forUser($student)->allows('view', $private))->toBeFalse()
        ->and(Gate::forUser($sarprasAdmin)->allows('view', $owned))->toBeTrue()
        ->and(Gate::forUser($sarprasAdmin)->allows('view', $private))->toBeFalse()
        ->and(Gate::forUser($kemahasiswaanAdmin)->allows('view', $private))->toBeTrue()
        ->and(Gate::forUser($superAdmin)->allows('view', $private))->toBeTrue()
        ->and(Gate::forUser($superAdmin)->allows('update', $private))->toBeFalse()
        ->and(Gate::forUser($sarprasAdmin)->allows('update', $owned))->toBeTrue();
});

it('lets only the token owner and authorized admins view anonymous violation reports', function (): void {
    config(['app.report_key' => str_repeat('d', 64)]);
    $reporter = User::factory()->create();
    $otherStudent = User::factory()->create();
    $kemahasiswaanAdmin = User::factory()->create(['role' => UserRole::ADMIN_KEMAHASISWAAN]);
    $superAdmin = User::factory()->create(['role' => UserRole::SUPER_ADMIN]);
    $violation = ReportType::create(['code' => ReportType::PELANGGARAN, 'name' => 'Dugaan Pelanggaran']);
    $token = Report::reporterTokenFor($reporter);
    $anonymousReport = makePolicyReport($violation, $reporter, [
        'reporter_id' => null,
        'reporter_token' => $token,
        'is_anonymous' => true,
        'requested_visibility' => Report::VISIBILITY_RAHASIA,
        'visibility_status' => Report::VISIBILITY_APPROVED,
        'approved_visibility' => Report::VISIBILITY_RAHASIA,
    ]);

    expect(Gate::forUser($reporter)->allows('view', $anonymousReport))->toBeTrue()
        ->and(Gate::forUser($otherStudent)->allows('view', $anonymousReport))->toBeFalse()
        ->and(Gate::forUser($kemahasiswaanAdmin)->allows('view', $anonymousReport))->toBeTrue()
        ->and(Gate::forUser($superAdmin)->allows('view', $anonymousReport))->toBeTrue()
        ->and($anonymousReport->reporter_id)->toBeNull()
        ->and($anonymousReport->reporter)->toBeNull();
});

function makePolicyReport(ReportType $type, User $reporter, array $attributes): Report
{
    $category = Category::create([
        'report_type_id' => $type->id,
        'name' => $type->name.' test',
        'is_active' => true,
    ]);

    return Report::create([
        'ticket_no' => 'TEST-'.fake()->unique()->numerify('####'),
        'report_type_id' => $type->id,
        'category_id' => $category->id,
        'title' => 'Laporan pengujian',
        'description' => 'Isi laporan pengujian.',
        'assigned_unit' => $type->assignedUnit(),
        ...$attributes,
    ]);
}
