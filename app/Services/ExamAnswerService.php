<?php

namespace App\Services;

use App\Exceptions\DataNotFound;
use App\Models\ExamAnswerScoreLog; // ✅ tambah import yang kurang
use App\Models\Question;
use App\Models\StudentExamAnswer;
use App\Models\StudentExamAttempt; // ✅ tambah import yang kurang
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExamAnswerService
{
    // Batas maksimum item per halaman untuk mencegah query terlalu besar
    private const MAX_PER_PAGE = 100;

    /**
     * Ambil semua jawaban ujian dengan pagination dan pencarian.
     */
    public function getAllExamAnswers(
        int $perPage = 5,
        string $search = "",
    ): LengthAwarePaginator {
        $perPage = min($perPage, self::MAX_PER_PAGE);

        return StudentExamAnswer::when(
            $search,
            fn($q) => $q->where("answer", "like", "%{$search}%"),
        )->paginate($perPage);
    }

    /**
     * Ambil semua jawaban berdasarkan attempt ID.
     */
    public function getExamAnswersByAttemptId(string $id): Collection
    {
        $attempt = StudentExamAttempt::with([
            "exam.questions",
            "student.user",
            "student.class",
        ])->findOrFail($id);

        $essayQuestions = $attempt->exam->questions->where("type", "Essay");

        $existingAnswers = StudentExamAnswer::with(["question"])
            ->where("student_exam_attempt_id", $id)
            ->whereIn("question_id", $essayQuestions->pluck("id"))
            ->get()
            ->keyBy("question_id");

        return $essayQuestions
            ->map(function ($question) use ($existingAnswers, $id, $attempt) {
                $answer = $existingAnswers->get($question->id);

                if ($answer) {
                    // Set relasi agar tidak lazy load
                    $answer->setRelation("studentExamAttempt", $attempt);
                    $answer->setRelation("question", $question);
                    return $answer;
                }

                // Instance kosong untuk soal yang belum dijawab
                $empty = new StudentExamAnswer();
                $empty->student_exam_attempt_id = $id;
                $empty->question_id = $question->id;
                $empty->answer = null;
                $empty->score = null;
                $empty->graded_by = null;
                $empty->graded_at = null;
                $empty->setRelation("question", $question);
                $empty->setRelation("studentExamAttempt", $attempt);

                return $empty;
            })
            ->values();
    }

    /**
     * Simpan jawaban satu soal (dipakai saat siswa menjawab satu per satu).
     * Berbeda dengan saveAnswersBulk yang dipakai saat submit semua sekaligus.
     */
    public function saveAnswer(
        string $attemptId,
        string $questionId,
        string $answer,
    ): StudentExamAnswer {
        $question = Question::where("id", $questionId)->first();

        if (!$question) {
            throw new DataNotFound("Soal tidak ditemukan");
        }

        // Default null karena essay tidak auto-grade
        $score = null;
        $isCorrect = null;

        // Hanya Multiple Choice yang bisa dinilai otomatis
        if ($question->type === "Multiple Choice") {
            $isCorrect = $answer === $question->correct_answer;
            $score = $isCorrect ? 100 : 0;
        }

        // Essay tidak dinilai di sini, score tetap null menunggu guru

        return DB::transaction(function () use (
            $attemptId,
            $questionId,
            $answer,
            $isCorrect,
            $score,
        ) {
            // updateOrCreate: update jika sudah ada, insert jika belum
            return StudentExamAnswer::updateOrCreate(
                [
                    "student_exam_attempt_id" => $attemptId,
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
    }

    /**
     * Simpan semua jawaban sekaligus saat siswa submit ujian.
     * Lebih efisien dari saveAnswer karena hanya 1 query upsert
     * dibanding N query updateOrCreate.
     */
    public function saveAnswersBulk(
        string $attemptId,
        array $submittedAnswers,
    ): array {
        if (empty($submittedAnswers)) {
            return [];
        }

        // Kumpulkan question_id dan answer yang valid saja
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

        // Tarik semua soal sekaligus (hindari N+1 query)
        $questions = Question::whereIn("id", array_unique($questionIds))
            ->get()
            ->keyBy("id");

        // Tarik semua jawaban yang sudah ada untuk attempt ini (hindari N+1 query)
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
                continue; // Skip jika soal tidak ditemukan di DB
            }

            $score = null;
            $isCorrect = null;

            // Multiple Choice: nilai otomatis berdasarkan jawaban benar/salah
            if ($question->type === "Multiple Choice") {
                $isCorrect = $answerText === $question->correct_answer;
                $score = $isCorrect ? 100 : 0;
            }

            // Essay: tidak dinilai otomatis, guru yang akan menilai nanti
            if ($question->type === "Essay") {
                $score = null;
                $isCorrect = null;
            }

            // Jika jawaban sudah ada pakai ID lama, jika belum buat UUID baru
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
            // Upsert: insert semua sekaligus, jika sudah ada maka update kolom yang disebutkan
            StudentExamAnswer::upsert(
                $upserts,
                ["student_exam_attempt_id", "question_id"], // kolom unik sebagai penentu
                ["answer", "score", "is_correct", "answered_at", "updated_at"], // kolom yang diupdate
            );
        }

        return $upserts;
    }

    /**
     * Dipanggil saat guru menilai jawaban essay siswa secara manual.
     * Score essay tidak bisa auto-grade karena jawabannya bisa bervariasi.
     */
    public function gradeEssay(
        string $attemptId,
        string $questionId,
        int $score,
        string $gradedBy,
    ): StudentExamAnswer {
        $answer = StudentExamAnswer::firstOrCreate(
            [
                "student_exam_attempt_id" => $attemptId,
                "question_id" => $questionId,
            ],
            [
                "answer" => null,
                "score" => null,
                "is_correct" => null,
                "answered_at" => null,
            ],
        );
        if (!$answer) {
            throw new DataNotFound("Jawaban siswa tidak ditemukan");
        }

        if ($answer->question->type !== "Essay") {
            throw new \Exception(
                "Hanya jawaban essay yang dapat dinilai manual",
                400,
            );
        }

        if ($score < 0 || $score > 100) {
            throw new \Exception("Score harus antara 0 dan 100", 400);
        }

        $scoreBefore = $answer->score ?? 0;

        DB::transaction(function () use (
            $answer,
            $score,
            $gradedBy,
            $attemptId,
            $questionId,
            $scoreBefore,
        ) {
            $answer->update([
                "score" => $score,
                "graded_by" => $gradedBy,
                "graded_at" => now(),
            ]);

            if ($scoreBefore !== $score) {
                ExamAnswerScoreLog::create([
                    "student_exam_answer_id" => $answer->id,
                    "attempt_id" => $attemptId,
                    "question_id" => $questionId,
                    "graded_by" => $gradedBy,
                    "score_before" => $scoreBefore,
                    "score_after" => $score,
                    "source" => "manual",
                ]);
            }

            // ✅ Hanya update pending_essay_count, recalculate ditangani gradeEssayAnswers()
            $pendingCount = StudentExamAnswer::where(
                "student_exam_attempt_id",
                $attemptId,
            )
                ->whereHas("question", fn($q) => $q->where("type", "Essay"))
                ->whereNull("graded_by")
                ->count();
            StudentExamAttempt::where("id", $attemptId)->update([
                "pending_essay_count" => $pendingCount,
            ]);
        });

        return $answer->fresh();
    }
}
