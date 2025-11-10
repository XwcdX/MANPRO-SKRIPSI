<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lecturer_period_quotas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('lecturer_id');
            $table->uuid('period_id');
            $table->unsignedInteger('max_students')->comment('Custom quota for this lecturer in this period');
            $table->timestamps();

            $table->foreign('lecturer_id')->references('id')->on('lecturers')->onDelete('cascade');
            $table->foreign('period_id')->references('id')->on('periods')->onDelete('cascade');

            $table->unique(['lecturer_id', 'period_id']);
            $table->index('period_id');
            $table->index('lecturer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lecturer_period_quotas');
    }
};
