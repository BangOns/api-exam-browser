<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table("student_exam_answers", function (Blueprint $table) {
            // Hapus duplikat dulu (sintaks PostgreSQL)
            DB::statement('
                DELETE FROM student_exam_answers
                WHERE id NOT IN (
                    SELECT DISTINCT ON (student_exam_attempt_id, question_id) id
                    FROM student_exam_answers
                    ORDER BY student_exam_attempt_id, question_id, created_at DESC
                )
            ');

            $table->unique(
                ["student_exam_attempt_id", "question_id"],
                "unique_attempt_question",
            );
        });
    }

    public function down(): void
    {
        Schema::table("student_exam_answers", function (Blueprint $table) {
            $table->dropUnique("unique_attempt_question");
        });
    }
};
