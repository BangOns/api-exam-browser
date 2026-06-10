<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table("student_exam_attempts", function (Blueprint $table) {
            $table
                ->boolean("is_score_final")
                ->default(false)
                ->after("total_score");
            $table
                ->unsignedTinyInteger("pending_essay_count")
                ->default(0)
                ->after("is_score_final");
        });
    }

    public function down(): void
    {
        Schema::table("student_exam_attempts", function (Blueprint $table) {
            $table->dropColumn(["is_score_final", "pending_essay_count"]);
        });
    }
};
