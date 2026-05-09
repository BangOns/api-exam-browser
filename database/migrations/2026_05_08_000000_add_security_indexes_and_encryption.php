<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Modify student_exam_attempts to encrypt security_config
        Schema::table('student_exam_attempts', function (Blueprint $table) {
            // Check if column exists before dropping
            if (Schema::hasColumn('student_exam_attempts', 'security_config')) {
                $table->dropColumn('security_config');
            }
            $table->text('security_config')->nullable()->after('exit_count'); // Will be encrypted by model
        });

        // Create indexes using raw SQL with IF NOT EXISTS (PostgreSQL 9.1+)
        DB::statement('CREATE INDEX IF NOT EXISTS "questions_lesson_id_index" ON "questions" ("lesson_id")');
        DB::statement('CREATE INDEX IF NOT EXISTS "questions_type_index" ON "questions" ("type")');

        // Composite indexes for common queries
        DB::statement('CREATE INDEX IF NOT EXISTS "student_exam_attempts_exam_id_student_id_index" ON "student_exam_attempts" ("exam_id", "student_id")');
        DB::statement('CREATE INDEX IF NOT EXISTS "student_exam_attempts_status_index" ON "student_exam_attempts" ("status")');
        DB::statement('CREATE INDEX IF NOT EXISTS "student_exam_attempts_created_at_index" ON "student_exam_attempts" ("created_at")');

        DB::statement('CREATE INDEX IF NOT EXISTS "student_exam_answers_student_exam_attempt_id_index" ON "student_exam_answers" ("student_exam_attempt_id")');
        DB::statement('CREATE INDEX IF NOT EXISTS "student_exam_answers_question_id_index" ON "student_exam_answers" ("question_id")');

        DB::statement('CREATE INDEX IF NOT EXISTS "exams_subject_id_index" ON "exams" ("subject_id")');
        DB::statement('CREATE INDEX IF NOT EXISTS "exams_class_id_index" ON "exams" ("class_id")');

        DB::statement('CREATE INDEX IF NOT EXISTS "students_class_id_index" ON "students" ("class_id")');
        DB::statement('CREATE INDEX IF NOT EXISTS "students_user_id_index" ON "students" ("user_id")');

        DB::statement('CREATE INDEX IF NOT EXISTS "teachers_user_id_index" ON "teachers" ("user_id")');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove indexes using IF EXISTS
        DB::statement('DROP INDEX IF EXISTS "questions_lesson_id_index"');
        DB::statement('DROP INDEX IF EXISTS "questions_type_index"');
        DB::statement('DROP INDEX IF EXISTS "student_exam_attempts_exam_id_student_id_index"');
        DB::statement('DROP INDEX IF EXISTS "student_exam_attempts_status_index"');
        DB::statement('DROP INDEX IF EXISTS "student_exam_attempts_created_at_index"');
        DB::statement('DROP INDEX IF EXISTS "student_exam_answers_student_exam_attempt_id_index"');
        DB::statement('DROP INDEX IF EXISTS "student_exam_answers_question_id_index"');
        DB::statement('DROP INDEX IF EXISTS "exams_subject_id_index"');
        DB::statement('DROP INDEX IF EXISTS "exams_class_id_index"');
        DB::statement('DROP INDEX IF EXISTS "students_class_id_index"');
        DB::statement('DROP INDEX IF EXISTS "students_user_id_index"');
        DB::statement('DROP INDEX IF EXISTS "teachers_user_id_index"');

        // Restore security_config column if needed
        Schema::table('student_exam_attempts', function (Blueprint $table) {
            if (Schema::hasColumn('student_exam_attempts', 'security_config')) {
                $table->dropColumn('security_config');
            }
            $table->json('security_config')->nullable();
        });
    }
};
