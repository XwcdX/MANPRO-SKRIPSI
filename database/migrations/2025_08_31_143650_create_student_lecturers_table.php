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
        Schema::create('student_lecturers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignUuid('lecturer_id')->constrained('lecturers')->onDelete('cascade');
            $table->tinyInteger('role')->comment('0=Supervisor 1, 1=Supervisor 2, 2=Examining Lecturer');
            $table->boolean('is_lead_examiner')->default(false)->comment('Only for examining lecturers');
            $table->date('assignment_date');
            $table->enum('status', ['active', 'inactive', 'completed'])->default('active');
            $table->timestamps();

            $table->unique(['student_id', 'lecturer_id', 'role'], 'unique_student_supervisor');
            $table->index('role', 'idx_student_lecturers_role');
            $table->index('status');
            $table->index(['student_id', 'status']);
            $table->index(['lecturer_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_lecturers');
    }
};
