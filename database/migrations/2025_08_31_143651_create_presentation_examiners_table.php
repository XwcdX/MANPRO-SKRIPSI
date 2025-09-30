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
        Schema::create('presentation_examiners', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('thesis_presentation_id')->constrained('thesis_presentations')->onDelete('cascade');
            $table->foreignUuid('lecturer_id')->constrained('lecturers')->onDelete('cascade');
            $table->boolean('is_lead_examiner')->default(false);
            $table->enum('attendance_status', ['scheduled', 'present', 'absent', 'excused'])->default('scheduled');
            $table->decimal('evaluation_score', 4, 2)->nullable()->comment('Score out of 100');
            $table->text('comments')->nullable();
            $table->timestamps();

            $table->unique(['thesis_presentation_id', 'lecturer_id'], 'unique_presentation_examiner');
            $table->index('attendance_status', 'idx_presentation_examiners_attendance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('presentation_examiners');
    }
};
