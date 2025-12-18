<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('topic_applications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignUuid('topic_id')->constrained('lecturer_topics')->onDelete('cascade');
            $table->foreignUuid('lecturer_id')->constrained('lecturers')->onDelete('cascade');
            $table->foreignUuid('period_id')->constrained('periods')->onDelete('cascade');
            $table->text('student_notes')->nullable();
            $table->text('lecturer_notes')->nullable();
            $table->enum('status', ['pending', 'accepted', 'declined', 'quota_full'])->default('pending');
            $table->timestamps();
            
            $table->unique(['student_id', 'period_id']);
            $table->index('status');
            $table->index(['lecturer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topic_applications');
    }
};
