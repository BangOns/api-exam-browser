<?php

namespace App\Services;

use App\Exceptions\DataNotFound;
use App\Models\Exam;
use App\Models\ExamSchedule;
use App\Models\ExamToken;
use App\Models\Student;
use App\Models\StudentExamAttempt;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\SecurityConfigService;
use Illuminate\Support\Facades\Log;

class ExamAttemptService
{
    private const MAX_PER_PAGE = 10;

    const IN_PROGRESS = "In Progress";
    const EXITED = "Exited";
    const SUBMITTED = "Submitted";

    /**
     * Ambil semua attempt dengan pagination dan pencarian.
     *
     * FIX: orWhereHas tanpa grouping menyebabkan query bocor ke semua record.
     * Sekarang di-wrap dengan where() closure agar OR hanya berlaku di dalam kondisi search.
     */
    // public function getAllExamAttempts(
    //     int $perPage = 5,
    //     string $search = "",
    //     string $examId = "",
    // ): LengthAwarePaginator {
    //     $perPage = min($perPage, self::MAX_PER_PAGE);

    //     return StudentExamAttempt::with(["exam", "student", "answers"])
    //         ->when($search, function ($q) use ($search) {
    //             $q->where(function ($inner) use ($search) {
    //                 $inner
    //                     ->whereHas(
    //                         "student",
    //                         fn($sq) => $sq->where(
    //                             "name",
    //                             "like",
    //                             "%{$search}%",
    //                         ),
    //                     )
    //                     ->orWhereHas(
    //                         "exam",
    //                         fn($eq) => $eq->where(
    //                             "title",
    //                             "like",
    //                             "%{$search}%",
    //                         ),
    //                     );
    //             });
    //         })
    //         ->when($examId, function ($q) use ($examId) {
    //             $q->whereHas("exam", fn($eq) => $eq->where("id", $examId));
    //         })
    //         ->paginate($perPage);
    // }
    public function getAllExamAttempts(
        int $perPage = 5,
        string $search = "",
        string $examId = "",
    ): LengthAwarePaginator {
        $perPage = min($perPage, self::MAX_PER_PAGE);

        return Student::with([
            "user", // ✅ tambahkan ini
            "class", // ✅ tambahkan ini
            "examAttempts" => fn($q) => $q->when(
                $examId,
                fn($q) => $q->where("exam_id", $examId),
            ),
            "examAttempts.exam",
        ])
            ->when($search, fn($q) => $q->where("name", "like", "%{$search}%"))
            ->when(
                $examId,
                fn($q) => $q->whereHas(
                    "examAttempts",
                    fn($inner) => $inner->where("exam_id", $examId),
                ),
            )
            ->latest()
            ->paginate($perPage);
    }
    /**
     * Generate token baru untuk exam, token sebelumnya otomatis non-aktif.
     */
    public function generateNewToken(string $examId): ExamToken
    {
        Log::info("[ExamToken] Token baru di-generate", ["exam_id" => $examId]);

        $exam = Exam::find($examId);
        if (!$exam) {
            throw new DataNotFound("Ujian tidak ditemukan");
        }

        $now = now();
        $dateNow = $now->toDateString();
        $timeNow = $now->toTimeString();

        $activeSchedule = ExamSchedule::where("exam_id", $examId)
            ->where("exam_date", $dateNow)
            ->where("start_time", "<=", $timeNow)
            ->where("end_time", ">=", $timeNow)
            ->first();

        if (!$activeSchedule) {
            throw new \Exception(
                "Token hanya dapat di-generate jika saat ini berada di dalam rentang waktu jadwal ujian.",
                403,
            );
        }

        return DB::transaction(function () use ($examId) {
            ExamToken::where("exam_id", $examId)->update([
                "is_active" => false,
            ]);

            $newTokenStr = strtoupper(Str::random(6));

            return ExamToken::create([
                "exam_id" => $examId,
                "token" => $newTokenStr,
                "is_active" => true,
            ]);
        });
    }

    /**
     * Student memasuki ujian menggunakan token.
     */
    public function enterExam(
        string $studentId,
        string $examId,
        string $token,
    ): StudentExamAttempt {
        $exam = Exam::find($examId);
        if (!$exam) {
            throw new DataNotFound("Ujian tidak ditemukan");
        }

        $configSecure = app(SecurityConfigService::class)->build();

        return DB::transaction(function () use (
            $studentId,
            $examId,
            $token,
            $configSecure,
        ) {
            $activeToken = ExamToken::where("exam_id", $examId)
                ->where("is_active", true)
                ->first();

            if (!$activeToken || !hash_equals($activeToken->token, $token)) {
                throw new \Exception(
                    "Token ujian tidak valid atau sudah kadaluarsa",
                    400,
                );
            }

            $attempt = StudentExamAttempt::where("exam_id", $examId)
                ->where("student_id", $studentId)
                ->lockForUpdate()
                ->first();

            // ✅ BELUM PERNAH MASUK → Buat attempt baru
            if (!$attempt) {
                return StudentExamAttempt::create([
                    "exam_id" => $examId,
                    "student_id" => $studentId,
                    "status" => self::IN_PROGRESS,
                    "started_at" => now(),
                    "security_config" => $configSecure,
                    "last_token_used" => $token,
                ]);
            }

            // ❌ SUDAH SUBMIT → Tolak permanen
            if ($attempt->status === self::SUBMITTED) {
                throw new \Exception(
                    "Anda sudah menyelesaikan ujian ini.",
                    403,
                );
            }

            // ❌ EXITED + TOKEN SAMA → Tolak, wajib pakai token baru
            if (
                $attempt->status === self::EXITED &&
                $attempt->last_token_used === $token
            ) {
                throw new \Exception(
                    "Token sudah pernah digunakan. Gunakan token baru untuk masuk kembali.",
                    403,
                );
            }

            // ✅ EXITED + TOKEN BARU → Izinkan masuk kembali
            if ($attempt->status === self::EXITED) {
                $attempt->update([
                    "status" => self::IN_PROGRESS,
                    "last_token_used" => $token,
                ]);

                return $attempt->fresh();
            }

            // ✅ IN PROGRESS → Reconnect (tab baru / refresh), biarkan lanjut
            if ($attempt->status === self::IN_PROGRESS) {
                return $attempt;
            }

            // ❌ Status tidak dikenali
            throw new \Exception("Status ujian tidak valid.", 500);
        });
    }

    /**
     * Student keluar dari ujian (sengaja/tidak sengaja).
     *
     * FIX: increment dipindahkan ke sebelum update agar keduanya
     * berada dalam satu urutan yang konsisten di dalam transaksi.
     */
    public function exitExam(
        string $studentId,
        string $examId,
        string $type,
    ): StudentExamAttempt {
        return DB::transaction(function () use ($studentId, $examId, $type) {
            $attempt = StudentExamAttempt::where("exam_id", $examId)
                ->where("student_id", $studentId)
                ->lockForUpdate()
                ->first();

            if (!$attempt) {
                throw new DataNotFound("Anda belum masuk ke ujian ini");
            }

            app(ExamViolationsService::class)->handleViolation($attempt, $type);

            if ($attempt->status === self::IN_PROGRESS) {
                // ✅ FIX: increment dulu, lalu update status — urutan konsisten
                $attempt->increment("exit_count");
                $attempt->update(["status" => self::EXITED]);
            }

            return $attempt->fresh();
        });
    }

    /**
     * Student mensubmit ujian.
     */
    public function submitExam(
        string $studentId,
        string $examId,
        array $submittedAnswers = [],
    ): StudentExamAttempt {
        return DB::transaction(function () use (
            $studentId,
            $examId,
            $submittedAnswers,
        ) {
            $attempt = StudentExamAttempt::where("exam_id", $examId)
                ->where("student_id", $studentId)
                ->lockForUpdate()
                ->first();

            if (!$attempt) {
                throw new DataNotFound("Anda belum masuk ke ujian ini");
            }

            if ($attempt->status === self::SUBMITTED) {
                return $attempt;
            }

            $examAnswerService = app(ExamAnswerService::class);
            $examAnswerService->saveAnswersBulk(
                $attempt->id,
                $submittedAnswers,
            );

            $answers = $attempt->answers()->with("question")->get();
            $totalScore = $answers->sum("score");

            // Hitung jumlah soal essay yang belum dinilai (score masih 0)
            $pendingEssayCount = $answers
                ->filter(
                    fn($a) => $a->question->type === "essay" && $a->score == 0,
                )
                ->count();

            $attempt->update([
                "status" => self::SUBMITTED,
                "submitted_at" => now(),
                "total_score" => $totalScore,
                // ✅ FIX: Tandai apakah score sudah final atau masih menunggu penilaian essay
                "is_score_final" => $pendingEssayCount === 0,
                "pending_essay_count" => $pendingEssayCount,
            ]);

            return $attempt->fresh();
        });
    }
}
