<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->index('status');
            $table->index('is_active');
            $table->index('email');
        });

        Schema::table('lecturers', function (Blueprint $table) {
            $table->index('is_active');
            $table->index('email');
            $table->index('title');
            $table->index('division_id');
        });

        Schema::table('student_lecturers', function (Blueprint $table) {
            $table->index('status');
            $table->index('role');
            $table->index(['student_id', 'status']);
            $table->index(['lecturer_id', 'status']);
        });

        Schema::table('supervision_applications', function (Blueprint $table) {
            $table->index('status');
            $table->index(['student_id', 'status']);
            $table->index(['lecturer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['is_active']);
            $table->dropIndex(['email']);
        });

        Schema::table('lecturers', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropIndex(['email']);
            $table->dropIndex(['title']);
            $table->dropIndex(['division_id']);
        });

        Schema::table('student_lecturers', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['role']);
            $table->dropIndex(['student_id', 'status']);
            $table->dropIndex(['lecturer_id', 'status']);
        });

        Schema::table('supervision_applications', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['student_id', 'status']);
            $table->dropIndex(['lecturer_id', 'status']);
        });
    }
};
