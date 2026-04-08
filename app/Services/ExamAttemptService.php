<?php

namespace App\Services;

use App\Exceptions\DataNotFound;
use App\Models\Exam;
use App\Models\ExamSchedule;
use App\Models\ExamToken;
use App\Models\StudentExamAttempt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExamAttemptService
{
    /**
     * Generate token baru untuk exam, token sebelumnya otomatis non-aktif.
     */
    public function generateNewToken(string $examId): ExamToken
    {
        $exam = Exam::find($examId);
        if (!$exam) {
            throw new DataNotFound('Ujian tidak ditemukan');
        }

        // Cek apakah ada jadwal ujian yang aktif saat ini
        $now = now();
        $dateNow = $now->toDateString();
        $timeNow = $now->toTimeString();

        $activeSchedule = ExamSchedule::where('exam_id', $examId)
            ->where('exam_date', $dateNow)
            ->where('start_time', '<=', $timeNow)
            ->where('end_time', '>=', $timeNow)
            ->first();

        if (!$activeSchedule) {
            throw new \Exception('Token hanya dapat di-generate jika saat ini berada di dalam rentang waktu jadwal ujian ujian.', 403);
        }

        return DB::transaction(function () use ($examId) {
            // Menonaktifkan token lama
            ExamToken::where('exam_id', $examId)->update(['is_active' => false]);

            // Membuat token baru
            $newTokenStr = strtoupper(Str::random(6)); // contoh A4B8XY
            
            return ExamToken::create([
                'exam_id' => $examId,
                'token' => $newTokenStr,
                'is_active' => true,
            ]);
        });
    }

    /**
     * Student memasuki ujian menggunakan token
     */
    public function enterExam(string $studentId, string $examId, string $token): StudentExamAttempt
    {
        $exam = Exam::find($examId);
        if (!$exam) {
            throw new DataNotFound('Ujian tidak ditemukan');
        }

        return DB::transaction(function () use ($studentId, $examId, $token) {
            $activeToken = ExamToken::where('exam_id', $examId)
                ->where('is_active', true)
                ->first();

            if (!$activeToken || $activeToken->token !== $token) {
                // Return generic error or we can throw custom Exception
                throw new \Exception('Token ujian tidak valid atau sudah kadaluarsa', 400);
            }

            $attempt = StudentExamAttempt::where('exam_id', $examId)
                ->where('student_id', $studentId)
                ->first();

            if (!$attempt) {
                // Belum ada attempt, buat baru
                return StudentExamAttempt::create([
                    'exam_id' => $examId,
                    'student_id' => $studentId,
                    'status' => 'In Progress',
                    'started_at' => now(),
                ]);
            }

            // Jika sudah ada
            if ($attempt->status === 'Submitted') {
                throw new \Exception('Anda sudah menyelesaikan ujian ini dan tidak bisa masuk kembali.', 403);
            }

            if ($attempt->status === 'Exited') {
                // Lanjutkan ujian, ubah status ke In Progress (wajib input token baru karena sudah tervalidasi di atas)
                $attempt->update([
                    'status' => 'In Progress'
                ]);
            }

            return $attempt;
        });
    }

    /**
     * Student keluar dari ujian (sengaja/tidak sengaja)
     */
    public function exitExam(string $studentId, string $examId): StudentExamAttempt
    {
        return DB::transaction(function () use ($studentId, $examId) {
            $attempt = StudentExamAttempt::where('exam_id', $examId)
                ->where('student_id', $studentId)
                ->first();

            if (!$attempt) {
                throw new DataNotFound('Anda belum masuk ke ujian ini');
            }

            if ($attempt->status === 'In Progress') {
                $attempt->update([
                    'status' => 'Exited',
                    'exit_count' => $attempt->exit_count + 1
                ]);
            }

            return $attempt;
        });
    }

    /**
     * Student mensubmit ujian
     */
    public function submitExam(string $studentId, string $examId): StudentExamAttempt
    {
        return DB::transaction(function () use ($studentId, $examId) {
            $attempt = StudentExamAttempt::where('exam_id', $examId)
                ->where('student_id', $studentId)
                ->first();

            if (!$attempt) {
                throw new DataNotFound('Anda belum masuk ke ujian ini');
            }

            if ($attempt->status !== 'Submitted') {
                $attempt->update([
                    'status' => 'Submitted',
                    'submitted_at' => now()
                ]);
            }

            return $attempt;
        });
    }
}
