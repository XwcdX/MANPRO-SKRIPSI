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
       Schema::create('thesis_titles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('abstract')->nullable();
            $table->year('completion_year');
            $table->string('student_name');
            $table->string('student_nrp')->index();
            $table->string('document_path')->nullable()->comment('Optional path to a PDF of the final thesis');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('thesis_titles');
    }
};
