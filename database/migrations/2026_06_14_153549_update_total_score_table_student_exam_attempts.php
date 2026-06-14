<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table("student_exam_attempts", function (Blueprint $table) {
            $table->decimal("total_score", 5, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table("student_exam_attempts", function (Blueprint $table) {
            $table->integer("total_score")->default(0)->change();
        });
    }
};
