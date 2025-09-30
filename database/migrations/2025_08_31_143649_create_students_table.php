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
        Schema::create('students', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->string('email', 100)->unique();
            $table->string('password');
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->string('thesis_title')->nullable();
            $table->tinyInteger('status')->default(0)->comment(' 0=New, 1=Title Submitted, 2=Title Declined, 3=Title Accepted & Forwarded, 4=Waiting Schedule, 5=Scheduled, 6=Thesis Declined, 7=Thesis Accepted & Waiting Final, 8=Completed');
            $table->text('head_division_comment')->nullable();
            $table->text('revision_notes')->nullable();
            $table->string('final_thesis_path', 500)->nullable();
            $table->date('due_date')->nullable();
            $table->timestamps();

            $table->index('status', 'idx_students_status');
            $table->index('email', 'idx_students_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
