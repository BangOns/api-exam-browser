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
        Schema::create('student_exam_answers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_exam_attempt_id');
            $table->uuid('question_id');
            $table->text('answer')->nullable();
            $table->integer('score')->nullable(); // null untuk essay yang belum dinilai
            $table->boolean('is_correct')->nullable(); // hanya untuk MCQ
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();

            $table->foreign('student_exam_attempt_id')
                ->references('id')->on('student_exam_attempts')->onUpdate('cascade')->onDelete('cascade');

            $table->foreign('question_id')
                ->references('id')->on('questions')->onUpdate('cascade')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_exam_answers');
    }
};
