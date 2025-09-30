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
        Schema::create('student_periods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignUuid('period_id')->constrained('periods')->onDelete('restrict');
            $table->date('enrollment_date');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['student_id', 'period_id'], 'unique_student_period');
            $table->index('is_active', 'idx_student_periods_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
