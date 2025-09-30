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
        Schema::create('thesis_presentations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('venue_id')->constrained('presentation_venues')->onDelete('restrict');
            $table->foreignUuid('schedule_id')->constrained('schedules')->onDelete('restrict');
            $table->foreignUuid('student_id')->constrained('students')->onDelete('restrict');
            $table->enum('presentation_type', ['proposal', 'final'])->default('final');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('thesis_presentations');
    }
};
