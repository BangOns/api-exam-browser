<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table("exams", function (Blueprint $table) {
            $table
                ->unsignedTinyInteger("essay_weight")
                ->default(0)
                ->after("status");
            $table
                ->unsignedTinyInteger("pg_weight")
                ->default(0)
                ->after("essay_weight");
        });
    }

    public function down(): void
    {
        Schema::table("exams", function (Blueprint $table) {
            $table->dropColumn(["essay_weight", "pg_weight"]);
        });
    }
};
