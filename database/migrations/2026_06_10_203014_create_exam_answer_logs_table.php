<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create("exam_answer_score_logs", function (Blueprint $table) {
            $table->uuid("id")->primary();
            $table->uuid("student_exam_answer_id");
            $table->uuid("attempt_id");
            $table->uuid("question_id");
            $table->uuid("graded_by");
            $table->integer("score_before")->default(0);
            $table->integer("score_after");
            $table->enum("source", ["auto", "manual"])->default("manual");
            $table->timestamps();

            $table
                ->foreign("student_exam_answer_id")
                ->references("id")
                ->on("student_exam_answers")
                ->onDelete("cascade");

            $table
                ->foreign("attempt_id")
                ->references("id")
                ->on("student_exam_attempts")
                ->onDelete("cascade");

            $table
                ->foreign("question_id")
                ->references("id")
                ->on("questions")
                ->onDelete("cascade");

            $table
                ->foreign("graded_by")
                ->references("id")
                ->on("users")
                ->onDelete("cascade");
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("exam_answer_score_logs");
    }
};
