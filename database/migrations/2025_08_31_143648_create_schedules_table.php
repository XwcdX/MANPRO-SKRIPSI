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
        Schema::create('schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('period_id')->after('id')->constrained('periods')->onDelete('restrict');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();
            
            $table->index('date', 'idx_schedules_date');
            $table->index('period_id', 'idx_supervision_applications_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
