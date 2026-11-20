<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $hasDeletedAccountReferences = DB::table('activity_logs as logs')
            ->leftJoin('users', 'users.id', '=', 'logs.user_id')
            ->whereNotNull('logs.user_id')
            ->whereNull('users.id')
            ->exists();

        if ($hasDeletedAccountReferences) {
            throw new RuntimeException('The activity log foreign key cannot be restored while logs refer to deleted accounts.');
        }

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users');
        });
    }
};
