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

        return StudentExamAttempt::with(['exam', 'student', 'answers'])
            ->when($search, function ($q) use ($search) {
                $q->whereHas('student', fn($sq) => $sq->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('exam', fn($eq) => $eq->where('title', 'like', "%{$search}%"));
            })
            ->paginate($perPage);
    }
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
            throw new \Exception('Token hanya dapat di-generate jika saat ini berada di dalam rentang waktu jadwal ujian.', 403);
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

            $attempt = StudentExamAttempt::where('exam_id', $examId)
                ->where('student_id', $studentId)
                ->lockForUpdate()
                ->first();
            // ✅ BELUM PERNAH MASUK → Buat attempt baru
            if (!$attempt) {
                return StudentExamAttempt::create([
                    'exam_id'         => $examId,
                    'student_id'      => $studentId,
                    'status'          => 'In Progress',
                    'started_at'      => now(),
                    'security_config' => $configSecure,
                    'last_token_used' => $token,
                ]);
            }

            // ❌ SUDAH SUBMIT → Tolak permanen
            if ($attempt->status === 'Submitted') {
                throw new \Exception('Anda sudah menyelesaikan ujian ini.', 403);
            }

            // ❌ EXITED + TOKEN SAMA → Tolak, wajib pakai token baru
            if ($attempt->status === 'Exited' && $attempt->last_token_used === $token) {
                throw new \Exception('Token sudah pernah digunakan. Gunakan token baru untuk masuk kembali.', 403);
            }

            // ✅ EXITED + TOKEN BARU → Izinkan masuk kembali
            if ($attempt->status === 'Exited') {
                $attempt->update([
                    'status'          => 'In Progress',
                    'last_token_used' => $token,
                ]);

                return $attempt->fresh(); // kembalikan data terbaru
            }

            // ✅ IN PROGRESS → Reconnect (tab baru / refresh)
            // Tidak reset started_at, cukup pastikan token tercatat
            if ($attempt->status === 'In Progress') {
                return $attempt; // biarkan lanjut, tidak perlu update apapun
            }

            // ❌ Status tidak dikenali
            // throw new \Exception('Status ujian tidak valid.', 500);
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
                $attempt->update(['status' => 'Exited']);
                $attempt->increment('exit_count');
            }

            return $attempt;
        });
        return $attemptRequest;
    }

    /**
     * Student mensubmit ujian
     */
    public function submitExam(string $studentId, string $examId, array $submittedAnswers = []): StudentExamAttempt
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
        return $attempt;
    }
}
