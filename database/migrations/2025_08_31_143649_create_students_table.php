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
        Schema::create('students', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->string('email', 100)->unique();
            $table->string('password');
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->string('thesis_title')->nullable();
            $table->text('thesis_description')->nullable();
            $table->tinyInteger('status')->default(0)->comment(' 0=Submit Title, 1=Choose Supervisor, 2=Upload Proposal, 3=Proposal Presentation, 4=Proposal Final, 5=Upload Thesis, 6=Thesis Presentation, 7=Thesis Final');
            $table->text('head_division_comment')->nullable();
            $table->text('revision_notes')->nullable();
            $table->string('final_thesis_path', 500)->nullable();
            $table->date('due_date')->nullable();
            $table->boolean('is_active')->default(true);
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
