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
        Schema::create('exam_questions', function (Blueprint $table) {
            $table->foreignUuid('exam_id')
                ->constrained('exams')
                ->cascadeOnDelete();

            $table->foreignUuid('question_id')
                ->constrained('questions')
                ->restrictOnDelete();

            $table->unsignedInteger('order')->nullable(); // urutan soal

            $table->primary(['exam_id', 'question_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_questions');
    }
};
