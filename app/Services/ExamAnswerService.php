<?php

namespace App\Services;

use App\Exceptions\DataNotFound;
use App\Models\Question;
use App\Models\StudentExamAnswer;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ExamAnswerService
{
    private const CACHE_TTL     = 60;

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
        $this->flushListCache();
        return $studentAnswer;
    }
    private function flushListCache(): void
    {
        // Jika pakai Redis / Memcached — gunakan tags (direkomendasikan)
        // Cache::tags([self::CACHE_LIST_PREFIX])->flush();

        // Jika pakai driver tanpa tags — flush seluruh cache
        // (pertimbangkan ganti ke Redis agar tidak flush semua data)
        Cache::flush();
    }
}
