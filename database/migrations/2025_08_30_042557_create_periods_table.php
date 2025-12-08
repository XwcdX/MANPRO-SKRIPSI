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
        Schema::create('periods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique()->comment('e.g., Odd 2025/2026, Even 2025/2026');
            $table->date('start_date');
            $table->date('end_date');
            $table->date('registration_end');
            $table->time('proposal_schedule_start_time')->default('08:00:00')->comment('Daily start time for proposal presentations');
            $table->time('proposal_schedule_end_time')->default('16:00:00')->comment('Daily end time for proposal presentations');
            $table->time('thesis_schedule_start_time')->default('08:00:00')->comment('Daily start time for thesis presentations');
            $table->time('thesis_schedule_end_time')->default('16:00:00')->comment('Daily end time for thesis presentations');
            $table->time('break_start_time')->default('12:00:00')->comment('Break start time (applies to all schedules)');
            $table->time('break_end_time')->default('13:00:00')->comment('Break end time (applies to all schedules)');
            $table->unsignedInteger('proposal_slot_duration')->default(45)->comment('Duration of each proposal presentation slot in minutes');
            $table->unsignedInteger('thesis_slot_duration')->default(45)->comment('Duration of each thesis presentation slot in minutes');
            $table->enum('status', [
                'upcoming',
                'registration_open',
                'proposal_in_progress',
                'proposal_hearing',
                'thesis_in_progress',
                'thesis',
                'completed',
                'archived'
            ])->default('upcoming');
            $table->unsignedInteger('default_quota')->default(12);
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('archived_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('periods');
    }
};
