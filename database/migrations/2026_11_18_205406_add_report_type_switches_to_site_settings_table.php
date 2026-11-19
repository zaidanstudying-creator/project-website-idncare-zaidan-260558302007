<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->boolean('facility_reports_enabled')->default(true);
            $table->boolean('aspiration_reports_enabled')->default(true);
            $table->boolean('violation_reports_enabled')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'facility_reports_enabled',
                'aspiration_reports_enabled',
                'violation_reports_enabled',
            ]);
        });
    }
};
