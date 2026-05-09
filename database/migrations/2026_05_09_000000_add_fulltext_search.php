<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - Setup PostgreSQL Full-Text Search
     */
    public function up(): void
    {
        // Enable PostgreSQL extensions
        DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gin');
        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');

        // ============================================================================
        // EXAMS TABLE - Full-Text Search Setup
        // ============================================================================
        Schema::table('exams', function (Blueprint $table) {
            // Add search_vector column using raw SQL for tsvector type
        });

        DB::statement('ALTER TABLE exams ADD COLUMN search_vector tsvector');
        DB::statement('UPDATE exams SET search_vector = to_tsvector(\'english\', name)');
        DB::statement('CREATE INDEX idx_exams_search_fts ON exams USING GiST(search_vector)');
        DB::statement('CREATE INDEX idx_exams_name_trigram ON exams USING GIN(name gin_trgm_ops)');

        // ============================================================================
        // QUESTIONS TABLE - Full-Text Search Setup
        // ============================================================================
        DB::statement('ALTER TABLE questions ADD COLUMN search_vector tsvector');
        DB::statement('UPDATE questions SET search_vector = to_tsvector(\'english\', question || \' \' || COALESCE(correct_answer, \'\'))');
        DB::statement('CREATE INDEX idx_questions_search_fts ON questions USING GiST(search_vector)');
        DB::statement('CREATE INDEX idx_questions_text_trigram ON questions USING GIN(question gin_trgm_ops)');

        // ============================================================================
        // SUBJECTS TABLE - Full-Text Search Setup
        // ============================================================================
        DB::statement('ALTER TABLE subjects ADD COLUMN search_vector tsvector');
        DB::statement('UPDATE subjects SET search_vector = to_tsvector(\'english\', name)');
        DB::statement('CREATE INDEX idx_subjects_search_fts ON subjects USING GiST(search_vector)');
        DB::statement('CREATE INDEX idx_subjects_name_trigram ON subjects USING GIN(name gin_trgm_ops)');

        // ============================================================================
        // CLASSES TABLE - Full-Text Search Setup
        // ============================================================================
        DB::statement('ALTER TABLE classes ADD COLUMN search_vector tsvector');
        DB::statement('UPDATE classes SET search_vector = to_tsvector(\'english\', name)');
        DB::statement('CREATE INDEX idx_classes_search_fts ON classes USING GiST(search_vector)');
        DB::statement('CREATE INDEX idx_classes_name_trigram ON classes USING GIN(name gin_trgm_ops)');

        // ============================================================================
        // LESSONS TABLE - Full-Text Search Setup
        // ============================================================================
        DB::statement('ALTER TABLE lessons ADD COLUMN search_vector tsvector');
        DB::statement('UPDATE lessons SET search_vector = to_tsvector(\'english\', name)');
        DB::statement('CREATE INDEX idx_lessons_search_fts ON lessons USING GiST(search_vector)');
        DB::statement('CREATE INDEX idx_lessons_name_trigram ON lessons USING GIN(name gin_trgm_ops)');

        // ============================================================================
        // ACTIVITY LOGS TABLE - Full-Text Search (for audit logs)
        // ============================================================================
        DB::statement('ALTER TABLE activity_logs ADD COLUMN search_vector tsvector');
        DB::statement('UPDATE activity_logs SET search_vector = to_tsvector(\'english\', name)');
        DB::statement('CREATE INDEX idx_activity_logs_search_fts ON activity_logs USING GiST(search_vector)');
    }

    /**
     * Reverse the migrations
     */
    public function down(): void
    {
        // Drop all indexes
        DB::statement('DROP INDEX IF EXISTS idx_exams_search_fts CASCADE');
        DB::statement('DROP INDEX IF EXISTS idx_exams_name_trigram CASCADE');

        DB::statement('DROP INDEX IF EXISTS idx_questions_search_fts CASCADE');
        DB::statement('DROP INDEX IF EXISTS idx_questions_text_trigram CASCADE');

        DB::statement('DROP INDEX IF EXISTS idx_subjects_search_fts CASCADE');
        DB::statement('DROP INDEX IF EXISTS idx_subjects_name_trigram CASCADE');

        DB::statement('DROP INDEX IF EXISTS idx_classes_search_fts CASCADE');
        DB::statement('DROP INDEX IF EXISTS idx_classes_name_trigram CASCADE');

        DB::statement('DROP INDEX IF EXISTS idx_lessons_search_fts CASCADE');
        DB::statement('DROP INDEX IF EXISTS idx_lessons_name_trigram CASCADE');

        DB::statement('DROP INDEX IF EXISTS idx_activity_logs_search_fts CASCADE');

        // Drop columns
        Schema::table('exams', function (Blueprint $table) {
            $table->dropColumn('search_vector');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('search_vector');
        });

        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn('search_vector');
        });

        Schema::table('classes', function (Blueprint $table) {
            $table->dropColumn('search_vector');
        });

        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn('search_vector');
        });

        Schema::table('activity_logs', function (Blueprint $table) {
            $table->dropColumn('search_vector');
        });
    }
};
