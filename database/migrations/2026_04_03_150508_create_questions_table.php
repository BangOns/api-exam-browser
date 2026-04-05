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
        Schema::create('questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('question');
            $table->foreignUuid('lessons_id')->constrained('lessons')->onUpdate('cascade')->onDelete('set null');
            $table->enum('type', ['Multiple Choice', 'Essay'])->default('Multiple Choice');
            $table->json('options')->nullable();         // [{label: "A", text: "..."}]
            $table->string('correct_answer')->nullable();  // untuk Multiple Choice
            $table->string('rubric')->nullable();          // untuk Essay
            $table->unsignedInteger('max_points')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
