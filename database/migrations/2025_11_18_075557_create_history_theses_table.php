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
        Schema::create('history_theses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained('students')->onDelete('cascade');
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->text('comment')->nullable();
            $table->tinyInteger('status')->default(0)->comment(' 0=Pending, 1=Revision, 2=Acc Supervisor, 3=Acc Kabid');
            $table->timestamps();

            $table->index(['student_id', 'created_at']);
            $table->index('student_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('history_theses');
    }
};
