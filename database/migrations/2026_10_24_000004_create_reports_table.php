<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table): void {
            $table->id();
            $table->string('ticket_no', 30)->unique();
            $table->foreignId('reporter_id')->nullable()->constrained('users');
            $table->string('reporter_token', 100)->nullable()->index();
            $table->foreignId('report_type_id')->constrained();
            $table->foreignId('category_id')->constrained();
            $table->foreignId('location_id')->nullable()->constrained();
            $table->string('title', 150);
            $table->text('description');
            $table->date('incident_date')->nullable();
            $table->string('related_party')->nullable();
            $table->string('status', 20)->default('dikirim');
            $table->string('requested_visibility', 10)->default('rahasia');
            $table->string('visibility_status', 15)->default('menunggu');
            $table->string('approved_visibility', 10)->nullable();
            $table->boolean('is_anonymous')->default(false);
            $table->string('assigned_unit', 20);
            $table->text('rejected_reason')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->unsignedTinyInteger('reopened_count')->default(0);
            $table->timestamps();
            $table->index(['status', 'report_type_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
