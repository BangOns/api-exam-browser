<?php

namespace App\Services;

use App\Exceptions\DataNotFound;
use App\Models\Exam;
use App\Models\ExamSchedule;
use App\Models\ExamToken;
use App\Models\StudentExamAttempt;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\SecurityConfigService;

class ExamAttemptService
{
    /**
     * Generate token baru untuk exam, token sebelumnya otomatis non-aktif.
     */
    private const CACHE_TTL     = 60;

    private const MAX_PER_PAGE = 10;
    public function getAllClasses(int $perPage = 5, string $search = ''): LengthAwarePaginator
    {
        // Batasi perPage agar tidak bisa di-abuse
        $perPage = min($perPage, self::MAX_PER_PAGE);


        return StudentExamAttempt::with('exam', 'answer')->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
            ->paginate($perPage);
    }
    public function generateNewToken(string $examId): ExamToken
    {
        $exam = Exam::find($examId);
        if (!$exam) {
            throw new DataNotFound('Ujian tidak ditemukan');
        }
        $examSchedule = ExamSchedule::where('exam_id', $examId)->first();
        if (!$examSchedule) {
            throw new DataNotFound('Jadwal ujian tidak ditemukan');
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
        $configSecure = app(SecurityConfigService::class)->build();

        return DB::transaction(function () use ($studentId, $examId, $token, $configSecure) {
            $activeToken = ExamToken::where('exam_id', $examId)
                ->where('is_active', true)
                ->first();

            if (!$activeToken || $activeToken->token !== $token) {
                throw new \Exception('Token ujian tidak valid atau sudah kadaluarsa', 400);
            }

            // Gunakan firstOrCreate untuk menangani race condition secara atomik
            // (Dipadukan dengan Unique Constraint di DB)
            $attempt = StudentExamAttempt::firstOrCreate(
                ['exam_id' => $examId, 'student_id' => $studentId],
                [
                    'status' => 'In Progress',
                    'started_at' => now(),
                    'security_config' => $configSecure,
                ]
            );

            // Jika attempt baru saja dibuat (wasRecentlyCreated), return langsung
            if ($attempt->wasRecentlyCreated) {
                return $attempt;
            }

            // Jika attempt sudah ada, cek statusnya
            if ($attempt->status === 'Submitted') {
                throw new \Exception('Anda sudah menyelesaikan ujian ini dan tidak bisa masuk kembali.', 403);
            }

            if ($attempt->status === 'Exited') {
                $attempt->update(['status' => 'In Progress']);
            }

            return $attempt;
        });
    }

    /**
     * Student keluar dari ujian (sengaja/tidak sengaja)
     */
    public function exitExam(string $studentId, string $examId, string $type): StudentExamAttempt
    {
        $attemptRequest = DB::transaction(function () use ($studentId, $examId, $type) {
            $attempt = StudentExamAttempt::where('exam_id', $examId)
                ->where('student_id', $studentId)
                ->first();

            if (!$attempt) {
                throw new DataNotFound('Anda belum masuk ke ujian ini');
            }
            app(ExamViolationsService::class)->handleViolation($attempt, $type);

            if ($attempt->status === 'In Progress') {
                $attempt->update([
                    'status' => 'Exited',
                    'exit_count' => $attempt->exit_count + 1
                ]);
            }

            return $attempt;
        });
        return $attemptRequest;
    }

    /**
     * Student mensubmit ujian
     */
    public function submitExam(string $studentId, string $examId, array $submittedAnswers = [])
    {
        $attempt = DB::transaction(function () use ($studentId, $examId, $submittedAnswers) {
            $attempt = StudentExamAttempt::where('exam_id', $examId)
                ->where('student_id', $studentId)
                ->first();

            if (!$attempt) {
                throw new DataNotFound('Anda belum masuk ke ujian ini');
            }

            if ($attempt->status === 'Submitted') {
                return $attempt; // sudah submit, tidak perlu proses lagi
            }

            // Simpan seluruh jawaban yang dipassing secara massal (Bulk Insert/Update)
            $examAnswerService = app(ExamAnswerService::class);
            $examAnswerService->saveAnswersBulk($attempt->id, $submittedAnswers);

            // Ambil semua jawaban (yang sudah dihitung score-nya otomatis oleh ExamAnswerService)
            $answers = $attempt->answers()->with('question')->get();

            // Total dari skor (essay biarkan 0 jika belum dinilai, karena saveAnswer default max 0 bila essay)
            $totalScore = $answers->sum('score');

            // Update attempt
            $attempt->update([
                'status' => 'Submitted',
                'submitted_at' => now(),
                'total_score' => $totalScore
            ]);

            return $attempt;
        });
    }
}
