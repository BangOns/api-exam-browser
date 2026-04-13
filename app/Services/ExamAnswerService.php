<?php

namespace App\Services;

use App\Exceptions\DataNotFound;
use App\Models\Question;
use App\Models\StudentExamAnswer;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExamAnswerService
{

    // Batas maksimum item per halaman
    private const MAX_PER_PAGE  = 100;
    public function getAllExamAnswers(int $perPage = 5, string $search = ''): LengthAwarePaginator
    {
        // Batasi perPage agar tidak bisa di-abuse
        $perPage = min($perPage, self::MAX_PER_PAGE);


        return StudentExamAnswer::when($search, fn($q) => $q->where('answer', 'like', "%{$search}%"))
            ->paginate($perPage);
    }

    public function saveAnswer($attempId, $questionId, $answer)
    {
        $question = Question::where('id', $questionId)->first();
        $score = 0;
        $isCorrect = false;

        if (!isset($question)) {
            throw new DataNotFound('Soal tidak ditemukan'); // Harus pakai throw, bukan return
        }

        if ($question->type === 'Multiple Choice') {
            $score = $answer === $question->correct_answer ? $question->max_points : 0;
            $isCorrect = $answer === $question->correct_answer;
        }

        $studentAnswer = DB::transaction(function () use ($attempId, $questionId, $answer, $isCorrect, $score) {
            // Gunakan updateOrCreate agar jawaban bisa diperbarui jika siswa mengganti jawaban
            return StudentExamAnswer::updateOrCreate(
                [
                    'student_exam_attempt_id' => $attempId,
                    'question_id' => $questionId,
                ],
                [
                    'answer' => $answer,
                    'score' => $score,
                    'is_correct' => $isCorrect,
                    'answered_at' => now(),
                ]
            );
        });
        return $studentAnswer;
    }

    public function saveAnswersBulk(string $attemptId, array $submittedAnswers)
    {
        if (empty($submittedAnswers)) {
            return [];
        }

        $questionIds = [];
        $validAnswers = [];
        foreach ($submittedAnswers as $entry) {
            if (isset($entry['question_id']) && isset($entry['answer'])) {
                $questionIds[] = $entry['question_id'];
                $validAnswers[$entry['question_id']] = $entry['answer'];
            }
        }

        if (empty($questionIds)) {
            return [];
        }

        // 1. Tarik semua data Questions sekaligus (N+1 Select dihindari)
        $questions = Question::whereIn('id', array_unique($questionIds))->get()->keyBy('id');

        // 2. Tarik semua jawaban yang sudah ada untuk attempt ini (N+1 UpdateOrCreate dihindari)
        $existingAnswers = StudentExamAnswer::where('student_exam_attempt_id', $attemptId)
            ->whereIn('question_id', array_unique($questionIds))
            ->get()
            ->keyBy('question_id');

        $now = now()->toDateTimeString();
        $upserts = [];

        foreach ($validAnswers as $questionId => $answerText) {
            $question = $questions->get($questionId);
            if (!$question) continue;

            $score = 0;
            $isCorrect = false;

            if ($question->type === 'Multiple Choice') {
                $isCorrect = ($answerText === $question->correct_answer);
                $score = $isCorrect ? $question->max_points : 0;
            }

            // Cek apakah data jawaban sudah ada. Jika ada pakai ID lama, jika belom buat ID baru UUID.
            $existing = $existingAnswers->get($questionId);
            $id = $existing ? $existing->id : (string) Str::uuid();

            $upserts[] = [
                'id' => $id,
                'student_exam_attempt_id' => $attemptId,
                'question_id' => $questionId,
                'answer' => $answerText,
                'score' => $score,
                'is_correct' => $isCorrect,
                'answered_at' => $now,
                'created_at' => $existing ? $existing->created_at->toDateTimeString() : $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($upserts)) {
            DB::transaction(function () use ($upserts) {
                // Upsert bulk berdasarkan primary key 'id'
                // Ini akan mengupdate data lama yang punya id sama, dan menginsert data dengan id baru
                StudentExamAnswer::upsert(
                    $upserts,
                    ['student_exam_attempt_id', 'question_id'],
                    ['answer', 'score', 'is_correct', 'answered_at', 'updated_at']
                );
            });
        }

        return $upserts;
    }
}
