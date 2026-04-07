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
        Schema::create('exams', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->foreignUuid('subject_id')->constrained('subjects')->onUpdate('cascade')->onDelete('cascade');
            $table->foreignUuid('class_id')->constrained('classes')->onUpdate('cascade')->onDelete('cascade');
            $table->enum('status', ['Active', 'Scheduled', 'Draft', 'Completed'])->default('Draft');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exams');
    }
};
