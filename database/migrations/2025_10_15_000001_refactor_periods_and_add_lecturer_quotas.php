<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('periods', function (Blueprint $table) {
            $table->dropColumn(['max_students', 'title_submission_deadline', 'supervision_selection_deadline', 'status', 'is_active', 'registration_start']);
            $table->unsignedInteger('default_quota')->default(12)
                ->comment('Default max students per lecturer for this period');
            $table->timestamp('archived_at')->nullable();
            $table->index('archived_at');
        });

        Schema::create('lecturer_period_quotas', function (Blueprint $table) {
            $table->id();
            $table->uuid('lecturer_id');
            $table->uuid('period_id');
            $table->unsignedInteger('max_students')->comment('Custom quota for this lecturer in this period');
            $table->timestamps();

            $table->foreign('lecturer_id')->references('id')->on('lecturers')->onDelete('cascade');
            $table->foreign('period_id')->references('id')->on('periods')->onDelete('cascade');

            $table->unique(['lecturer_id', 'period_id']);
            $table->index('period_id');
            $table->index('lecturer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lecturer_period_quotas');

        Schema::table('periods', function (Blueprint $table) {
            $table->dropIndex(['archived_at']);
            $table->dropColumn(['default_quota', 'archived_at']);
            $table->unsignedInteger('max_students')->nullable();
            $table->date('title_submission_deadline')->nullable();
            $table->date('supervision_selection_deadline')->nullable();
            $table->date('registration_start')->nullable();
            $table->boolean('is_active')->default(false);
            $table->enum('status', ['upcoming', 'registration_open', 'in_progress', 'completed', 'archived'])->default('upcoming');
        });
    }
};
