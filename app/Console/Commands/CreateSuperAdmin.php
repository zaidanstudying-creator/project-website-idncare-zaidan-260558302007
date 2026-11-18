<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

#[Signature('idn:super-admin:create')]
#[Description('Buat akun Super Admin secara interaktif dan aman.')]
class CreateSuperAdmin extends Command
{
    public function handle(ActivityLogService $activityLogs): int
    {
        if (! $this->input->isInteractive()) {
            $this->error('Jalankan perintah ini dari terminal interaktif agar password dapat dimasukkan secara tersembunyi.');

            return self::FAILURE;
        }

        $userCode = text(
            label: 'ID login Super Admin',
            default: 'SUPER-001',
            required: true,
            validate: function (string $value): ?string {
                if (strlen($value) > 50 || ! preg_match('/^[A-Za-z0-9_-]+$/', $value)) {
                    return 'ID hanya boleh berisi huruf, angka, tanda hubung, atau garis bawah (maksimal 50 karakter).';
                }

                return User::query()->where('user_code', $value)->exists()
                    ? 'ID tersebut sudah digunakan. Pilih ID login lain.'
                    : null;
            },
        );

        $name = text(
            label: 'Nama Super Admin',
            default: 'Super Admin',
            required: true,
            validate: fn (string $value): ?string => mb_strlen($value) > 255
                ? 'Nama maksimal 255 karakter.'
                : null,
        );

        $initialPassword = password(
            label: 'Password awal (minimal 8 karakter)',
            required: true,
            validate: fn (string $value): ?string => mb_strlen($value) < 8
                ? 'Password minimal 8 karakter.'
                : null,
        );
        $passwordConfirmation = password('Ulangi password awal', required: true);

        if (! hash_equals($initialPassword, $passwordConfirmation)) {
            $this->error('Konfirmasi password tidak cocok. Jalankan kembali perintah untuk mencoba lagi.');

            return self::FAILURE;
        }

        if (User::query()->where('user_code', $userCode)->exists()) {
            $this->error('ID tersebut baru saja digunakan. Jalankan kembali perintah dengan ID yang berbeda.');

            return self::FAILURE;
        }

        $user = DB::transaction(function () use ($userCode, $name, $initialPassword, $activityLogs): User {
            $user = User::query()->create([
                'user_code' => $userCode,
                'name' => $name,
                'role' => UserRole::SUPER_ADMIN,
                'is_active' => true,
                'must_change_password' => true,
                'password' => $initialPassword,
            ]);

            $activityLogs->record(null, 'super_admin_created_via_console', $user, [], [
                'role' => UserRole::SUPER_ADMIN->value,
                'must_change_password' => true,
            ]);

            return $user;
        });

        $this->info("Akun Super Admin {$user->user_code} berhasil dibuat.");
        $this->line('Password awal tidak ditampilkan dan harus diganti setelah login pertama.');

        return self::SUCCESS;
    }
}
