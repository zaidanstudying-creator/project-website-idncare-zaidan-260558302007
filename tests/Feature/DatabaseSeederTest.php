<?php

use App\Enums\UserRole;
use App\Models\Report;
use App\Models\ReportAttachment;
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
        ->and(Report::query()->count())->toBe(12);

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

    $this->seed(DatabaseSeeder::class);

    expect(User::query()->count())->toBe(4)
        ->and(Report::query()->count())->toBe(12)
        ->and(User::query()->where('user_code', 'MHS-0001')->value('password'))->toBe($existingHash);
});
