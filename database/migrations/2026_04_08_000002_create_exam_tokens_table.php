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
        Schema::create('exam_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('exam_id');
            $table->string('token')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamp('expired_at')->nullable();
            $table->timestamps();

            $table->foreign('exam_id')->references('id')->on('exams')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_tokens');
    }
};
