<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('supervision_applications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('period_id')->constrained('periods')->onDelete('restrict');
            $table->foreignUuid('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignUuid('lecturer_id')->constrained('lecturers')->onDelete('cascade');
            $table->tinyInteger('proposed_role')->comment('0=Supervisor 1, 1=Supervisor 2');
            $table->text('student_notes')->nullable()->comment('Pesan dari mahasiswa kepada dosen');
            $table->text('lecturer_notes')->nullable()->comment('Catatan/alasan dari dosen');
            $table->enum('status', ['pending', 'accepted', 'declined', 'canceled', 'changed'])->default('pending');
            $table->timestamps();
            
            $table->unique(['student_id', 'lecturer_id', 'proposed_role'], 'unique_student_lecturer_application');
            $table->index('status', 'idx_supervision_applications_status');
            $table->index('period_id', 'idx_supervision_applications_period');
            $table->index(['student_id', 'status']);
            $table->index(['lecturer_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supervision_applications');
    }
};
