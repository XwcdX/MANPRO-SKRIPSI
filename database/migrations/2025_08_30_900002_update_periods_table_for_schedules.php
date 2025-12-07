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
        Schema::table('periods', function (Blueprint $table) {
            $table->dropColumn([
                'proposal_hearing_start',
                'proposal_hearing_end',
                'thesis_start',
                'thesis_end'
            ]);

            $table->time('proposal_schedule_start_time')->default('08:00:00')->comment('Daily start time for proposal presentations');
            $table->time('proposal_schedule_end_time')->default('16:00:00')->comment('Daily end time for proposal presentations');
            
            $table->time('thesis_schedule_start_time')->default('08:00:00')->comment('Daily start time for thesis presentations');
            $table->time('thesis_schedule_end_time')->default('16:00:00')->comment('Daily end time for thesis presentations');
            
            $table->time('break_start_time')->default('12:00:00')->comment('Break start time (applies to all schedules)');
            $table->time('break_end_time')->default('13:00:00')->comment('Break end time (applies to all schedules)');
            
            $table->unsignedInteger('proposal_slot_duration')->default(45)->comment('Duration of each proposal presentation slot in minutes');
            $table->unsignedInteger('thesis_slot_duration')->default(45)->comment('Duration of each thesis presentation slot in minutes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('periods', function (Blueprint $table) {
            // Restore old fields
            $table->date('proposal_hearing_start')->nullable();
            $table->date('proposal_hearing_end')->nullable();
            $table->date('thesis_start')->nullable();
            $table->date('thesis_end')->nullable();

            // Remove new fields
            $table->dropColumn([
                'proposal_schedule_start_time',
                'proposal_schedule_end_time',
                'thesis_schedule_start_time',
                'thesis_schedule_end_time',
                'break_start_time',
                'break_end_time',
                'proposal_slot_duration',
                'thesis_slot_duration'
            ]);
        });
    }
};
