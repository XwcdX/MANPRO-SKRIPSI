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
        Schema::create('student_status_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignUuid('period_id')->nullable()->constrained('periods')->onDelete('set null');
            $table->tinyInteger('previous_status');
            $table->tinyInteger('new_status');
            $table->foreignUuid('changed_by')->nullable()->comment('Lecturer who made the change')->constrained('lecturers')->onDelete('set null');
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_status_history');
    }
};
