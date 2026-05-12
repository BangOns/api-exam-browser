<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {

            // Hapus foreign key dulu
            $table->dropForeign(['subject_id']);
            $table->dropForeign(['class_id']);

            // Hapus column
            $table->dropColumn(['subject_id', 'class_id']);

            // Tambah lesson_id
            $table->foreignUuid('lesson_id')
                ->after('name')
                ->constrained('lessons')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {

            // Hapus foreign lesson
            $table->dropForeign(['lesson_id']);
            $table->dropColumn('lesson_id');

            // Balikin column lama
            $table->foreignUuid('subject_id')
                ->constrained('subjects')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreignUuid('class_id')
                ->constrained('classes')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }
};
