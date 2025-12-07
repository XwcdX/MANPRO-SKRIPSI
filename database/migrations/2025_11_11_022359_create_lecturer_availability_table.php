<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lecturer_availability', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('lecturer_id')->constrained('lecturers')->onDelete('cascade');
            $table->foreignUuid('period_schedule_id')->constrained('period_schedules')->onDelete('cascade');
            $table->enum('type', ['proposal_hearing', 'thesis'])->comment('Type of schedule');
            $table->date('date');
            $table->string('time_slot')->comment('30-minute slots based on period schedule time');
            $table->boolean('is_available')->default(false)->comment('false = busy, true = available');
            $table->timestamps();

            $table->unique(['lecturer_id', 'period_schedule_id', 'type', 'date', 'time_slot'], 'lecturer_availability_unique');
            $table->index(['period_schedule_id', 'type', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lecturer_availability');
    }
};
