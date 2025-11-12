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
            $table->date('proposal_hearing_start')->nullable();
            $table->date('proposal_hearing_end')->nullable();
            $table->date('thesis_start')->nullable();
            $table->date('thesis_end')->nullable();
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
