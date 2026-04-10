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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // siapa yang melakukan
            $table->foreignUuid('user_id')->constrained('users')->onUpdate('cascade')->onDelete('set null');

            // aksi
            $table->string('action'); // create, update, delete, submit, violation

            // modul
            $table->string('module'); // question, exam, setting, attempt

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
