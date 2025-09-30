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
        Schema::create('lecturer_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('lecturer_id')->constrained('lecturers')->onDelete('cascade');
            $table->foreignUuid('schedule_id')->constrained('schedules')->onDelete('cascade');
            $table->tinyInteger('status')->default(1)->comment('0=Not Available, 1=Available, 2=Assigned');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['lecturer_id', 'schedule_id'], 'unique_lecturer_schedule');
            $table->index('status', 'idx_lecturer_schedules_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lecturer_schedules');
    }
};
