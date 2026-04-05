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
            $table->string('subject', 100);
            $table->string('target_class', 50)->nullable();
            $table->enum('status', ['Active', 'Scheduled', 'Draft', 'Completed'])->default('Draft');
            $table->unsignedInteger('students')->default(0);
            $table->string('token', 50)->unique()->nullable();
            $table->unsignedInteger('timer')->nullable(); // dalam menit
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
