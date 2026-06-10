<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table("student_exam_answers", function (Blueprint $table) {
            $table->uuid("graded_by")->nullable()->after("score");
            $table->timestamp("graded_at")->nullable()->after("graded_by");

            $table
                ->foreign("graded_by")
                ->references("id")
                ->on("users")
                ->onDelete("set null");
        });
    }

    public function down(): void
    {
        Schema::table("student_exam_answers", function (Blueprint $table) {
            $table->dropForeign(["graded_by"]);
            $table->dropColumn(["graded_by", "graded_at"]);
        });
    }
};
