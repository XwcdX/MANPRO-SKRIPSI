<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->foreignUuid('division_id')->nullable()->after('email')->constrained('divisions')->onDelete('set null');
            $table->index('division_id');
        });

        Schema::table('history_proposals', function (Blueprint $table) {
            $table->foreignUuid('division_id')->nullable()->after('student_id')->constrained('divisions')->onDelete('set null');
            $table->index('division_id');
        });

        Schema::table('history_theses', function (Blueprint $table) {
            $table->foreignUuid('division_id')->nullable()->after('student_id')->constrained('divisions')->onDelete('set null');
            $table->index('division_id');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['division_id']);
            $table->dropColumn('division_id');
        });

        Schema::table('history_proposals', function (Blueprint $table) {
            $table->dropForeign(['division_id']);
            $table->dropColumn('division_id');
        });

        Schema::table('history_theses', function (Blueprint $table) {
            $table->dropForeign(['division_id']);
            $table->dropColumn('division_id');
        });
    }
};
