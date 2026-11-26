<?php

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Report;
use App\Models\ReportAttachment;
use App\Models\ReportStatusHistory;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('seeds local demo roles and reports without changing an existing demo account password', function (): void {
    $demoPassword = 'Demo-IdnCare-2026!';
    $reportKey = str_repeat('test-only-report-token-key-', 2);
    config([
        'app.demo_user_password' => $demoPassword,
        'app.report_key' => $reportKey,
    ]);
    Storage::fake('local');

    $existingHash = Hash::make('Existing-Password-123!');
    User::factory()->create([
        'user_code' => 'MHS-0001',
        'password' => $existingHash,
        'must_change_password' => false,
    ]);

    $this->seed(DatabaseSeeder::class);

    expect(User::query()->count())->toBe(4)
        ->and(User::query()->where('role', UserRole::MAHASISWA->value)->count())->toBe(1)
        ->and(User::query()->where('role', UserRole::ADMIN_SARPRAS->value)->count())->toBe(1)
        ->and(User::query()->where('role', UserRole::ADMIN_KEMAHASISWAAN->value)->count())->toBe(1)
        ->and(User::query()->where('role', UserRole::SUPER_ADMIN->value)->count())->toBe(1)
        ->and(User::query()->where('user_code', 'MHS-0001')->value('password'))->toBe($existingHash)
        ->and(Hash::check($demoPassword, User::query()->where('user_code', 'SUPER-001')->value('password')))->toBeTrue()
        ->and(Report::query()->count())->toBe(12)
        ->and(ReportStatusHistory::query()->get()->every(fn (ReportStatusHistory $history): bool => $history->created_at !== null))->toBeTrue();

    $anonymousViolations = Report::query()
        ->whereHas('reportType', fn ($query) => $query->where('code', 'pelanggaran'))
        ->whereNull('reporter_id')
        ->whereNotNull('reporter_token')
        ->get();
    $studentId = User::query()->where('user_code', 'MHS-0001')->value('id');

    expect($anonymousViolations)->toHaveCount(2)
        ->and($anonymousViolations->every(fn (Report $report): bool => hash_equals(
            hash_hmac('sha256', (string) $studentId, $reportKey),
            $report->reporter_token,
        )))->toBeTrue();

    $completedFacility = Report::query()
        ->where('status', Report::STATUS_SELESAI)
        ->whereHas('reportType', fn ($query) => $query->where('code', 'fasilitas'))
        ->firstOrFail();
    $afterPhoto = ReportAttachment::query()
        ->where('report_id', $completedFacility->id)
        ->where('kind', 'after')
        ->firstOrFail();

    expect(Storage::disk('local')->exists($afterPhoto->file_path))->toBeTrue()
        ->and(Report::query()->distinct()->pluck('status')->all())->toContain(
            Report::STATUS_DIKIRIM,
            Report::STATUS_DIVERIFIKASI,
            Report::STATUS_DIPROSES,
            Report::STATUS_SELESAI,
            Report::STATUS_DITOLAK,
        );

    $existingDemoReport = Report::query()->where('ticket_no', 'IDN-DEMO-0001')->firstOrFail();
    $existingDemoReport->update(['title' => 'Judul demo yang sudah diperiksa']);
    $existingDemoCategory = Category::query()->where('name', 'Listrik')->firstOrFail();
    $existingDemoCategory->update(['is_active' => false]);

    $this->seed(DatabaseSeeder::class);

    expect(User::query()->count())->toBe(4)
        ->and(Report::query()->count())->toBe(12)
        ->and(User::query()->where('user_code', 'MHS-0001')->value('password'))->toBe($existingHash)
        ->and(Report::query()->where('ticket_no', 'IDN-DEMO-0001')->value('title'))->toBe('Judul demo yang sudah diperiksa')
        ->and(Category::query()->where('name', 'Listrik')->value('is_active'))->toBeFalse();
});

it('fails clearly before writing demo data when the demo password is missing', function (): void {
    config(['app.demo_user_password' => null]);

    expect(fn () => $this->seed(DatabaseSeeder::class))
        ->toThrow(LogicException::class, 'Set DEMO_USER_PASSWORD');

    expect(User::query()->count())->toBe(0);
});

it('refuses to seed demo accounts outside local and testing environments', function (): void {
    config(['app.demo_user_password' => 'Test-only-Demo-Password-2026!']);
    app()->detectEnvironment(fn (): string => 'production');

    expect(fn () => app(DatabaseSeeder::class)->run())
        ->toThrow(LogicException::class, 'limited to local and testing environments');

    expect(User::query()->count())->toBe(0);
});
