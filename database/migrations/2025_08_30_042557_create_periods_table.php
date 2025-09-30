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
            $table->date('registration_start')->comment('When students can start registering');
            $table->date('registration_end')->comment('Last day for registration');
            $table->date('supervision_selection_deadline')->nullable()->comment('Deadline for selecting supervisors');
            $table->date('title_submission_deadline')->nullable()->comment('Deadline for submitting thesis titles');
            $table->boolean('is_active')->default(false)->comment('Indicates the current registration period');
            $table->enum('status', ['upcoming', 'registration_open', 'in_progress', 'completed', 'archived'])
                ->default('upcoming');
            $table->unsignedInteger('max_students')->nullable()->comment('Maximum students allowed in this period');
            $table->timestamps();

            $table->index('status', 'idx_periods_status');
            $table->index('is_active', 'idx_periods_active');
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
