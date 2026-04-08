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
        Schema::create('exam_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('exam_id')->constrained('exams')->onUpdate('cascade')->onDelete('cascade');
            $table->date('exam_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('duration')->after('end_time')->comment('Durasi ujian dalam menit');
            $table->string('status')->default('draft');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_schedules');
    }
};
