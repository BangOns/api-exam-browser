<?php

namespace App\Services;

use App\Exceptions\DataNotFound;
use App\Http\Requests\ExamAttempt\SubmitExamRequest;
use App\Models\Question;
use App\Models\StudentExamAnswer;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExamAnswerService
{
    // Batas maksimum item per halaman
    private const MAX_PER_PAGE = 100;
    public function getAllExamAnswers(
        int $perPage = 5,
        string $search = "",
    ): LengthAwarePaginator {
        // Batasi perPage agar tidak bisa di-abuse
        $perPage = min($perPage, self::MAX_PER_PAGE);

        return StudentExamAnswer::when(
            $search,
            fn($q) => $q->where("answer", "like", "%{$search}%"),
        )->paginate($perPage);
    }
    public function getExamAnswersByAttemptId(string $id): Collection
    {
        return StudentExamAnswer::where("student_exam_attempt_id", $id)->get();
    }
    public function saveAnswer($attempId, $questionId, $answer)
    {
        $question = Question::where("id", $questionId)->first();
        $score = 0;
        $isCorrect = false;

        if (!isset($question)) {
            throw new DataNotFound("Soal tidak ditemukan"); // Harus pakai throw, bukan return
        }

        if ($question->type === "Multiple Choice") {
            $score =
                $answer === $question->correct_answer
                    ? $question->max_points
                    : 0;
            $isCorrect = $answer === $question->correct_answer;
        }

        $studentAnswer = DB::transaction(function () use (
            $attempId,
            $questionId,
            $answer,
            $isCorrect,
            $score,
        ) {
            // Gunakan updateOrCreate agar jawaban bisa diperbarui jika siswa mengganti jawaban
            return StudentExamAnswer::updateOrCreate(
                [
                    "student_exam_attempt_id" => $attempId,
                    "question_id" => $questionId,
                ],
                [
                    "answer" => $answer,
                    "score" => $score,
                    "is_correct" => $isCorrect,
                    "answered_at" => now(),
                ],
            );
        });
        return $studentAnswer;
    }

    public function saveAnswersBulk(
        string $attemptId,
        array $submittedAnswers,
    ): array {
        if (empty($submittedAnswers)) {
            return [];
        }

        $questionIds = [];
        $validAnswers = [];
        foreach ($submittedAnswers as $entry) {
            if (isset($entry["question_id"]) && isset($entry["answer"])) {
                $questionIds[] = $entry["question_id"];
                $validAnswers[$entry["question_id"]] = $entry["answer"];
            }
        }

        if (empty($questionIds)) {
            return [];
        }

        $questions = Question::whereIn("id", array_unique($questionIds))
            ->get()
            ->keyBy("id");

        $existingAnswers = StudentExamAnswer::where(
            "student_exam_attempt_id",
            $attemptId,
        )
            ->whereIn("question_id", array_unique($questionIds))
            ->get()
            ->keyBy("question_id");

        $now = now()->toDateTimeString();
        $upserts = [];

        foreach ($validAnswers as $questionId => $answerText) {
            $question = $questions->get($questionId);
            if (!$question) {
                continue;
            }

            $score = null;
            $isCorrect = null;

            if ($question->type === "Multiple Choice") {
                $isCorrect = $answerText === $question->correct_answer;
                $score = $isCorrect ? 100 : 0;
            }

            // ✅ Essay: simpan jawaban saja, score null menunggu guru
            if ($question->type === "Essay") {
                $score = null;
                $isCorrect = null;
            }

            $existing = $existingAnswers->get($questionId);
            $id = $existing ? $existing->id : (string) Str::uuid();

            $upserts[] = [
                "id" => $id,
                "student_exam_attempt_id" => $attemptId,
                "question_id" => $questionId,
                "answer" => $answerText,
                "score" => $score,
                "is_correct" => $isCorrect,
                "answered_at" => $now,
                "created_at" => $existing
                    ? $existing->created_at->toDateTimeString()
                    : $now,
                "updated_at" => $now,
            ];
        }

        if (!empty($upserts)) {
            StudentExamAnswer::upsert(
                $upserts,
                ["student_exam_attempt_id", "question_id"],
                ["answer", "score", "is_correct", "answered_at", "updated_at"],
            );
        }

        return $upserts;
    }

    /**
     * Dipanggil saat guru menilai essay
     * Validasi hanya pembuat exam yang bisa menilai
     */
    public function gradeEssay(
        string $attemptId,
        string $questionId,
        int $score,
        string $gradedBy, // auth()->id() dari controller
    ): StudentExamAnswer {
        $answer = StudentExamAnswer::where(
            "student_exam_attempt_id",
            $attemptId,
        )
            ->where("question_id", $questionId)
            ->firstOrFail();

        $scoreBefore = $answer->score ?? 0;

        DB::transaction(function () use (
            $answer,
            $score,
            $gradedBy,
            $attemptId,
            $questionId,
            $scoreBefore,
        ) {
            // Update jawaban
            $answer->update([
                "score" => $score,
                "graded_by" => $gradedBy,
                "graded_at" => now(),
            ]);

            // Simpan log perubahan nilai
            if ($scoreBefore !== $score) {
                ExamAnswerScoreLog::create([
                    "id" => (string) Str::uuid(),
                    "student_exam_answer_id" => $answer->id,
                    "attempt_id" => $attemptId,
                    "question_id" => $questionId,
                    "graded_by" => $gradedBy,
                    "score_before" => $scoreBefore,
                    "score_after" => $score,
                    "source" => "manual",
                ]);
            }

            // Cek apakah semua essay sudah dinilai
            $pendingCount = StudentExamAnswer::where(
                "student_exam_attempt_id",
                $attemptId,
            )
                ->whereHas("question", fn($q) => $q->where("type", "Essay"))
                ->whereNull("graded_by")
                ->count();

            $attempt = StudentExamAttempt::find($attemptId);
            $attempt->update([
                "pending_essay_count" => $pendingCount,
            ]);

            // Jika semua essay sudah dinilai → recalculate final score
            if ($pendingCount === 0) {
                app(ExamAttemptService::class)->recalculateTotalScore(
                    $attemptId,
                );
            }
        });

        return $answer->fresh();
    }
}
