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
        Schema::create('period_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('period_id')->constrained('periods')->onDelete('cascade');
            $table->enum('type', ['proposal_hearing', 'thesis_defense'])->comment('Type of schedule: proposal hearing or thesis defense');
            $table->date('start_date')->comment('Start date of this schedule session');
            $table->date('end_date')->comment('End date of this schedule session');
            $table->date('deadline')->comment('Deadline for submission');
            $table->timestamps();

            $table->index('period_id');
            $table->index('type');
            $table->index(['start_date', 'end_date']);
            
            $table->index(['period_id', 'start_date', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('period_schedules');
    }
};
