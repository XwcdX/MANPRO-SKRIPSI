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
        Schema::table('thesis_presentations', function (Blueprint $table) {
            $table->dropForeign(['period_id']);
            $table->dropColumn('period_id');

            $table->foreignUuid('period_schedule_id')
                ->after('id')
                ->constrained('period_schedules')
                ->onDelete('cascade')
                ->comment('Links presentation to specific proposal hearing or thesis defense schedule');

            $table->index(['period_schedule_id', 'presentation_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('thesis_presentations', function (Blueprint $table) {
            $table->dropForeign(['period_schedule_id']);
            $table->dropColumn('period_schedule_id');

            $table->foreignUuid('period_id')
                ->after('id')
                ->constrained('periods')
                ->onDelete('cascade');
        });
    }
};
