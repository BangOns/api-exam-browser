<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table("students", function (Blueprint $table) {
            $table->dropForeign(["class_id"]);
            $table->dropColumn("class_id");
        });

        Schema::table("students", function (Blueprint $table) {
            $table
                ->foreignUuid("class_id")
                ->constrained("classes")
                ->onDelete("cascade");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table("students", function (Blueprint $table) {
            $table->dropForeign(["class_id"]);
            $table->dropColumn("class_id");
        });

        Schema::table("students", function (Blueprint $table) {
            $table
                ->foreignUuid("class_id")
                ->nullable()
                ->constrained("classes")
                ->onDelete("set null");
        });
    }
};
