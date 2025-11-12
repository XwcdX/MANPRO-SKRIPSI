<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('periods', function (Blueprint $table) {
            $table->time('schedule_start_time')->default('07:30:00')->after('thesis_end');
            $table->time('schedule_end_time')->default('18:00:00')->after('schedule_start_time');
        });
    }

    public function down(): void
    {
        Schema::table('periods', function (Blueprint $table) {
            $table->dropColumn(['schedule_start_time', 'schedule_end_time']);
        });
    }
};
