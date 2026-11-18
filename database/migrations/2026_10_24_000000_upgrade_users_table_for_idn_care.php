<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $missingColumns = collect([
            'user_code' => fn (Blueprint $table) => $table->string('user_code', 50)->nullable()->unique(),
            'role' => fn (Blueprint $table) => $table->enum('role', ['mahasiswa', 'admin_sarpras', 'admin_kemahasiswaan', 'super_admin'])->default('mahasiswa'),
            'unit_or_prodi' => fn (Blueprint $table) => $table->string('unit_or_prodi')->nullable(),
            'is_active' => fn (Blueprint $table) => $table->boolean('is_active')->default(true),
            'must_change_password' => fn (Blueprint $table) => $table->boolean('must_change_password')->default(true),
            'is_dorm_resident' => fn (Blueprint $table) => $table->boolean('is_dorm_resident')->default(false),
        ])->filter(fn ($addColumn, string $column): bool => ! Schema::hasColumn('users', $column));

        if ($missingColumns->isNotEmpty()) {
            Schema::table('users', function (Blueprint $table) use ($missingColumns): void {
                foreach ($missingColumns as $addColumn) {
                    $addColumn($table);
                }
            });
        }

        DB::table('users')->whereNull('user_code')->orderBy('id')->chunkById(500, function ($users): void {
            foreach ($users as $user) {
                DB::table('users')->where('id', $user->id)->update([
                    'user_code' => 'LEGACY-'.str_pad((string) $user->id, 12, '0', STR_PAD_LEFT),
                ]);
            }
        });

        if (Schema::hasColumn('users', 'user_code')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('user_code', 50)->nullable(false)->change();
            });
        }

        if (Schema::hasColumn('users', 'email') && DB::table('users')->count() === 0) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropUnique(['email']);
                $table->dropColumn(['email', 'email_verified_at']);
            });
        }

        if (Schema::hasTable('password_reset_tokens') && DB::table('password_reset_tokens')->count() === 0) {
            Schema::drop('password_reset_tokens');
        }
    }

    public function down(): void
    {
        if (DB::table('users')->exists()) {
            throw new LogicException('The users migration cannot be rolled back while user records exist.');
        }

        if (! Schema::hasColumn('users', 'email')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
            });
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'user_code', 'role', 'unit_or_prodi', 'is_active', 'must_change_password', 'is_dorm_resident',
            ]);
        });

        if (! Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table): void {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }
    }
};
