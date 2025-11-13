<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('division_lecturer', function (Blueprint $table) {
            $table->foreignUuid('lecturer_id')->constrained('lecturers')->onDelete('cascade');
            $table->foreignUuid('division_id')->constrained('divisions')->onDelete('cascade');
            $table->timestamps();
            
            $table->primary(['lecturer_id', 'division_id']);
        });

        Schema::table('lecturers', function (Blueprint $table) {
            $table->foreignUuid('primary_division_id')->nullable()->after('division_id')->constrained('divisions')->onDelete('set null');
        });

        DB::table('lecturers')->whereNotNull('division_id')->get()->each(function ($lecturer) {
            DB::table('lecturers')->where('id', $lecturer->id)->update([
                'primary_division_id' => $lecturer->division_id
            ]);
            
            DB::table('division_lecturer')->insert([
                'lecturer_id' => $lecturer->id,
                'division_id' => $lecturer->division_id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        Schema::table('lecturers', function (Blueprint $table) {
            $table->dropForeign(['division_id']);
            $table->dropColumn('division_id');
        });

        Schema::table('supervision_applications', function (Blueprint $table) {
            $table->foreignUuid('division_id')->nullable()->after('lecturer_id')->constrained('divisions')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('supervision_applications', function (Blueprint $table) {
            $table->dropForeign(['division_id']);
            $table->dropColumn('division_id');
        });

        Schema::table('lecturers', function (Blueprint $table) {
            $table->foreignUuid('division_id')->nullable()->after('title')->constrained('divisions')->onDelete('set null');
        });

        DB::table('lecturers')->whereNotNull('primary_division_id')->get()->each(function ($lecturer) {
            DB::table('lecturers')->where('id', $lecturer->id)->update([
                'division_id' => $lecturer->primary_division_id
            ]);
        });

        Schema::table('lecturers', function (Blueprint $table) {
            $table->dropForeign(['primary_division_id']);
            $table->dropColumn('primary_division_id');
        });

        Schema::dropIfExists('division_lecturer');
    }
};
