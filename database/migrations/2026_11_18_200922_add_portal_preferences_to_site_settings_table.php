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
            $table->string('campus_name')->default('IDN CARE');
            $table->string('logo_path')->nullable();
            $table->string('tagline')->default('Suarakan, laporkan, pantau.');
            $table->string('support_contact')->nullable();
            $table->text('announcement_text')->nullable();
            $table->boolean('announcement_enabled')->default(false);
            $table->string('theme')->default('light');
            $table->unsignedTinyInteger('font_scale')->default(100);
            $table->string('maintenance_title')->default('Waduh, maaf ya!');
            $table->text('maintenance_message')->nullable();
            $table->timestamp('maintenance_updated_at')->nullable();
            $table->foreignId('maintenance_updated_by')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropForeign(['maintenance_updated_by']);
            $table->dropColumn([
                'campus_name', 'logo_path', 'tagline', 'support_contact', 'announcement_text',
                'announcement_enabled', 'theme', 'font_scale', 'maintenance_title',
                'maintenance_message', 'maintenance_updated_at', 'maintenance_updated_by',
            ]);
        });
    }
};
