<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Location;
use App\Models\Report;
use App\Models\ReportAttachment;
use App\Models\ReportStatusHistory;
use App\Models\ReportType;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use LogicException;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (! app()->environment('local', 'testing')) {
            throw new LogicException('DatabaseSeeder is limited to local and testing environments.');
        }

        $demoPassword = config('app.demo_user_password');

        if (! is_string($demoPassword) || $demoPassword === '') {
            throw new LogicException('Set DEMO_USER_PASSWORD in the local environment before seeding demo accounts.');
        }

        $users = collect([
            ['user_code' => 'MHS-0001', 'name' => 'Mahasiswa Demo', 'role' => UserRole::MAHASISWA, 'unit_or_prodi' => 'Teknologi rekayasa perangkat lunak', 'nim' => '20260001', 'angkatan' => 2024],
            ['user_code' => 'ADM-SARPRA', 'name' => 'Admin Sarpras Demo', 'role' => UserRole::ADMIN_SARPRAS, 'unit_or_prodi' => 'Sarana dan Prasarana'],
            ['user_code' => 'ADM-KEMAH', 'name' => 'Admin Kemahasiswaan Demo', 'role' => UserRole::ADMIN_KEMAHASISWAAN, 'unit_or_prodi' => 'Kemahasiswaan'],
            ['user_code' => 'SUPER-001', 'name' => 'Super Admin Demo', 'role' => UserRole::SUPER_ADMIN, 'unit_or_prodi' => null],
        ])->mapWithKeys(function (array $attributes) use ($demoPassword): array {
            $userAttributes = [...$attributes, 'password' => $demoPassword, 'is_active' => true, 'must_change_password' => true];

            $user = User::query()->firstOrCreate(
                ['user_code' => $attributes['user_code']],
                $userAttributes,
            );

            if ($user->wasRecentlyCreated && Schema::hasColumn('users', 'email')) {
                $user->forceFill(['email' => strtolower($attributes['user_code']).'@local.invalid'])->save();
            }

            return [$attributes['role']->value => $user];
        });

        $types = collect([
            ReportType::FACILITAS => 'Laporan Fasilitas',
            ReportType::ASPIRASI => 'Aspirasi',
            ReportType::PELANGGARAN => 'Laporan Dugaan Pelanggaran',
        ])->mapWithKeys(fn (string $name, string $code): array => [
            $code => ReportType::firstOrCreate(['code' => $code], ['name' => $name]),
        ]);

        $categories = [
            ReportType::FACILITAS => ['Listrik', 'Plumbing', 'Kebersihan'],
            ReportType::ASPIRASI => ['Akademik', 'Kegiatan Mahasiswa', 'Lingkungan Kampus'],
            ReportType::PELANGGARAN => ['Etika dan Perilaku', 'Keamanan', 'Lainnya'],
        ];

        foreach ($categories as $typeCode => $names) {
            foreach ($names as $name) {
                Category::firstOrCreate(
                    ['report_type_id' => $types[$typeCode]->id, 'name' => $name],
                    ['is_active' => true],
                );
            }
        }

        $locations = collect(['Gedung Utama', 'Perpustakaan', 'Area Kampus'])->mapWithKeys(fn (string $name): array => [
            $name => Location::firstOrCreate(['name' => $name], ['is_active' => true]),
        ]);

        $student = $users[UserRole::MAHASISWA->value];
        $sarpras = $users[UserRole::ADMIN_SARPRAS->value];
        $kemahasiswaan = $users[UserRole::ADMIN_KEMAHASISWAAN->value];

        foreach ($this->demoReports($types, $student, $locations) as $index => $data) {
            $report = Report::query()
                ->withoutGlobalScope('active')
                ->firstOrCreate(['ticket_no' => $data['ticket_no']], $data['attributes']);
            $this->seedStatusHistory($report, $data, $student, $sarpras, $kemahasiswaan);

            foreach ($data['attachments'] as $kind) {
                $this->seedAttachment($report, $kind, $data['anonymous_violation'] ? null : $student->id);
            }
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function demoReports($types, User $student, $locations): array
    {
        $specifications = [
            [ReportType::FACILITAS, 'Kran air perlu diperbaiki', 'Kran di dekat ruang kelas terus menetes.', 'selesai', 'umum', 'disetujui', 'umum', 'Gedung Utama', false, ['before', 'after']],
            [ReportType::FACILITAS, 'Lampu lorong mati', 'Lampu lorong lantai dua tidak menyala sejak kemarin.', 'diproses', 'rahasia', 'menunggu', null, 'Gedung Utama', false, ['before']],
            [ReportType::FACILITAS, 'Tempat sampah penuh', 'Tempat sampah di area baca perlu lebih sering dikosongkan.', 'dikirim', 'umum', 'menunggu', null, 'Perpustakaan', true, []],
            [ReportType::FACILITAS, 'Pintu ruang kelas macet', 'Pintu kelas sulit dibuka dan sudah diperiksa.', 'ditolak', 'rahasia', 'menunggu', null, 'Gedung Utama', false, []],
            [ReportType::ASPIRASI, 'Usulan jam layanan perpustakaan', 'Mohon pertimbangkan jam layanan yang lebih panjang saat pekan ujian.', 'diverifikasi', 'umum', 'menunggu', null, 'Perpustakaan', false, []],
            [ReportType::ASPIRASI, 'Tambah ruang diskusi mahasiswa', 'Ruang diskusi bersama akan membantu kegiatan kelompok.', 'dikirim', 'rahasia', 'menunggu', null, 'Gedung Utama', false, []],
            [ReportType::ASPIRASI, 'Perlu area teduh di taman', 'Beberapa bangku taman belum memiliki peneduh.', 'diproses', 'umum', 'disetujui', 'umum', 'Area Kampus', true, []],
            [ReportType::ASPIRASI, 'Informasi kegiatan lebih terpusat', 'Usulan agar informasi kegiatan mahasiswa lebih mudah ditemukan.', 'selesai', 'rahasia', 'menunggu', null, 'Gedung Utama', false, []],
            [ReportType::PELANGGARAN, 'Dugaan pelanggaran anonim 1', 'Contoh laporan dugaan untuk data demo, tanpa menyertakan identitas nyata.', 'dikirim', 'umum', 'menunggu', 'umum', 'Gedung Utama', true, ['evidence']],
            [ReportType::PELANGGARAN, 'Dugaan pelanggaran anonim 2', 'Contoh kedua untuk menunjukkan penyimpanan laporan anonim.', 'diproses', 'rahasia', 'ditolak', 'umum', 'Area Kampus', true, ['evidence']],
            [ReportType::PELANGGARAN, 'Dugaan pelanggaran non-anonim', 'Contoh laporan dugaan yang disampaikan dengan identitas.', 'diverifikasi', 'umum', 'menunggu', 'umum', 'Perpustakaan', false, []],
            [ReportType::PELANGGARAN, 'Dugaan pelanggaran ditolak', 'Contoh laporan demo yang perlu dilengkapi sebelum dapat diproses.', 'ditolak', 'rahasia', 'disetujui', 'umum', 'Gedung Utama', false, []],
        ];

        return collect($specifications)->values()->map(function (array $specification, int $index) use ($types, $student, $locations): array {
            [$typeCode, $title, $description, $status, $requested, $visibilityStatus, $approved, $locationName, $anonymous, $attachments] = $specification;
            $isViolation = $typeCode === ReportType::PELANGGARAN;
            $type = $types[$typeCode];
            $category = $type->categories()->orderBy('id')->first();
            $ticketNo = sprintf('IDN-DEMO-%04d', $index + 1);
            $statusOrder = [
                Report::STATUS_DIKIRIM,
                Report::STATUS_DIVERIFIKASI,
                Report::STATUS_DIPROSES,
                Report::STATUS_SELESAI,
            ];

            if ($status === Report::STATUS_DITOLAK) {
                $statusOrder = [Report::STATUS_DIKIRIM, Report::STATUS_DITOLAK];
            } else {
                $statusOrder = array_slice($statusOrder, 0, array_search($status, $statusOrder, true) + 1);
            }

            return [
                'ticket_no' => $ticketNo,
                'anonymous' => $anonymous,
                'anonymous_violation' => $anonymous && $isViolation,
                'attachments' => $attachments,
                'status_order' => $statusOrder,
                'attributes' => [
                    'reporter_id' => $anonymous && $isViolation ? null : $student->id,
                    'reporter_token' => $anonymous && $isViolation ? Report::reporterTokenFor($student) : null,
                    'report_type_id' => $type->id,
                    'category_id' => $category->id,
                    'location_id' => $locations[$locationName]->id,
                    'title' => $title,
                    'description' => $description,
                    'incident_date' => $isViolation ? now()->subDays($index + 1)->toDateString() : null,
                    'related_party' => $isViolation ? 'Tidak dicantumkan pada data demo' : null,
                    'status' => $status,
                    'requested_visibility' => $isViolation ? Report::VISIBILITY_RAHASIA : $requested,
                    'visibility_status' => $isViolation ? Report::VISIBILITY_APPROVED : $visibilityStatus,
                    'approved_visibility' => $isViolation ? Report::VISIBILITY_RAHASIA : $approved,
                    'is_anonymous' => $anonymous,
                    'assigned_unit' => $type->assignedUnit(),
                    'rejected_reason' => $status === Report::STATUS_DITOLAK ? 'Data demo perlu dilengkapi atau diverifikasi ulang.' : null,
                    'completed_at' => $status === Report::STATUS_SELESAI ? now()->subDays(3) : null,
                    'confirmed_at' => null,
                    'reopened_count' => 0,
                ],
            ];
        })->all();
    }

    private function seedStatusHistory(Report $report, array $data, User $student, User $sarpras, User $kemahasiswaan): void
    {
        $previousStatus = null;
        $admin = $report->assigned_unit === ReportType::UNIT_SARPRAS ? $sarpras : $kemahasiswaan;

        foreach ($data['status_order'] as $position => $status) {
            $isSubmission = $position === 0;
            $changedBy = $isSubmission && $data['anonymous_violation'] ? null : ($isSubmission ? $student->id : $admin->id);

            ReportStatusHistory::firstOrCreate(
                ['report_id' => $report->id, 'to_status' => $status],
                [
                    'from_status' => $previousStatus,
                    'note' => $status === Report::STATUS_DITOLAK ? 'Data demo: perlu dilengkapi.' : null,
                    'changed_by' => $changedBy,
                    'created_at' => now()->subDays(count($data['status_order']) - $position),
                ],
            );

            $previousStatus = $status;
        }
    }

    private function seedAttachment(Report $report, string $kind, ?int $uploadedBy): void
    {
        $path = "demo/{$report->ticket_no}-{$kind}.png";
        $attachment = ReportAttachment::firstOrCreate(
            ['report_id' => $report->id, 'kind' => $kind],
            ['file_path' => $path, 'uploaded_by' => $uploadedBy, 'created_at' => now()],
        );

        if ($attachment->wasRecentlyCreated && ! Storage::disk('local')->exists($attachment->file_path)) {
            $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/lS8AAAAASUVORK5CYII=', true);
            Storage::disk('local')->put($attachment->file_path, $png);
        }
    }
}
