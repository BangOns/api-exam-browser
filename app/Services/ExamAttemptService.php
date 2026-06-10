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

    public function getAllExamAttempts(
        int $perPage = 5,
        string $search = "",
        string $examId = "",
    ): LengthAwarePaginator {
        $perPage = min($perPage, self::MAX_PER_PAGE);

        return StudentExamAttempt::with([
            "student.user",
            "student.class",
            "exam",
        ])
            ->when($examId, function ($query) use ($examId) {
                $query->where("exam_id", $examId);
            })
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->whereHas("student.user", function ($sq) use ($search) {
                        $sq->where("full_name", "like", "%{$search}%")->orWhere(
                            "username",
                            "like",
                            "%{$search}%",
                        );
                    })->orWhereHas("student", function ($sq) use ($search) {
                        $sq->where("nisn", "like", "%{$search}%");
                    });
                });
            })
            ->latest()
            ->paginate($perPage);
    }

    public function generateNewToken(string $examId): ExamToken
    {
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

            if ($attempt->status === self::SUBMITTED) {
                throw new \Exception(
                    "Anda sudah menyelesaikan ujian ini.",
                    403,
                );
            }

            if (
                $attempt->status === self::EXITED &&
                $attempt->last_token_used === $token
            ) {
                throw new \Exception(
                    "Token sudah pernah digunakan. Gunakan token baru untuk masuk kembali.",
                    403,
                );
            }

            if ($attempt->status === self::EXITED) {
                $attempt->update([
                    "status" => self::IN_PROGRESS,
                    "last_token_used" => $token,
                ]);

                return $attempt->fresh();
            }

            if ($attempt->status === self::IN_PROGRESS) {
                return $attempt;
            }

            throw new \Exception("Status ujian tidak valid.", 500);
        });
    }

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
                $attempt->increment("exit_count");
                $attempt->update(["status" => self::EXITED]);
            }

            return $attempt->fresh();
        });
    }

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

            $examAnswerService = app(ExamAnswerService::class);
            $examAnswerService->saveAnswersBulk(
                $attempt->id,
                $submittedAnswers,
            );

            // Ambil exam beserta questions untuk basis perhitungan
            $exam = $attempt->exam->load("questions");

            // Hitung pending essay (essay yang belum dinilai guru)
            $pendingEssayCount = $exam->questions
                ->filter(fn($q) => $q->type === "Essay")
                ->count();

            // Hitung score sementara (hanya dari PG, essay masih 0)
            $answers = $attempt->answers()->with("question")->get();
            $totalScore = $this->calculateWeightedScore($answers, $exam);

            $attempt->update([
                "status" => self::SUBMITTED,
                "submitted_at" => now(),
                "total_score" => $totalScore,
                "is_score_final" => $pendingEssayCount === 0,
                "pending_essay_count" => $pendingEssayCount,
            ]);

            return $attempt->fresh();
        });
    }

    /**
     * Dipanggil setelah guru selesai menilai semua essay.
     * Menghitung ulang total_score berdasarkan bobot essay & PG.
     */
    public function recalculateTotalScore(string $attemptId): void
    {
        $attempt = StudentExamAttempt::with([
            "answers.question",
            "exam.questions",
        ])->findOrFail($attemptId);

        $totalScore = $this->calculateWeightedScore(
            $attempt->answers,
            $attempt->exam,
        );

        $attempt->update([
            "total_score" => $totalScore,
            "is_score_final" => true,
        ]);
    }

    /**
     * Hitung weighted score berdasarkan bobot essay & PG dari exam.
     * Basis perhitungan dari exam_questions (bukan jawaban siswa)
     * agar soal yang tidak dijawab tetap dihitung sebagai 0.
     */
    private function calculateWeightedScore(
        \Illuminate\Support\Collection $answers,
        \App\Models\Exam $exam,
    ): float {
        $examQuestions = $exam->questions;

        $essayQuestions = $examQuestions->filter(
            fn($q) => $q->type === "Essay",
        );
        $pgQuestions = $examQuestions->filter(fn($q) => $q->type !== "Essay");

        $essayWeight = $exam->essay_weight ?? 0;
        $pgWeight = $exam->pg_weight ?? 0;

        // Index jawaban siswa by question_id untuk lookup O(1)
        $answersByQuestionId = $answers->keyBy("question_id");

        $essayScore = 0;
        if ($essayQuestions->count() > 0) {
            $totalEssay = $essayQuestions->sum(function ($q) use (
                $answersByQuestionId,
            ) {
                $answer = $answersByQuestionId->get($q->id);
                return $answer?->score ?? 0;
            });
            $avgEssay = $totalEssay / $essayQuestions->count();
            $essayScore = ($avgEssay * $essayWeight) / 100;
        }

        $pgScore = 0;
        if ($pgQuestions->count() > 0) {
            $totalPg = $pgQuestions->sum(function ($q) use (
                $answersByQuestionId,
            ) {
                $answer = $answersByQuestionId->get($q->id);
                return $answer?->score ?? 0;
            });
            $avgPg = $totalPg / $pgQuestions->count();
            $pgScore = ($avgPg * $pgWeight) / 100;
        }

        return round($essayScore + $pgScore, 2);
    }
}
