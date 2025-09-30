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
        Schema::create('lecturer_topics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('lecturer_id')->constrained('lecturers')->onDelete('cascade');
            $table->foreignUuid('period_id')->after('lecturer_id')
                ->constrained('periods')->onDelete('cascade')
                ->comment('Topics are period-specific');
            $table->string('topic');
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('student_quota')->default(1);
            $table->boolean('is_available')->default(true)->comment('Toggles off if the topic is taken');
            $table->timestamps();

            $table->index('period_id', 'idx_lecturer_topics_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lecturer_topics');
    }
};
