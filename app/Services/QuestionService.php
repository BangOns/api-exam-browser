<?php

namespace App\Services;

use App\Exceptions\DataNotFound;
use App\Models\Question;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class QuestionService
{
    // Durasi cache dalam detik
    private const CACHE_TTL     = 60;

    // Batas maksimum item per halaman
    private const MAX_PER_PAGE  = 100;
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }
    public function getAllQuestions(int $perPage = 5, string $search = ''): LengthAwarePaginator
    {
        // Batasi perPage agar tidak bisa di-abuse
        $perPage = min($perPage, self::MAX_PER_PAGE);


        return Question::with('lesson.subject', 'lesson.class')->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
            ->paginate($perPage);
    }
    public function getQuestionById($id)
    {
        $question = Question::with('lesson.subject', 'lesson.class')->where('id', $id)->first();
        if (!$question) {
            throw new DataNotFound('Pertanyaan tidak ditemukan');
        }
        return $question;
    }
    public function createQuestion(array $data)
    {
        if ($data['type'] === 'Multiple Choice') {
            if (empty($data['options']) || empty($data['correct_answer'])) {
                throw new \Exception('Multiple Choice must have options and correct answer');
            }
        }

        if ($data['type'] === 'Essay' && empty($data['rubric'])) {
            throw new \Exception('Essay must have rubric');
        }
        if (isset($data['options'])) {
            $data['options'] = json_encode($data['options']);
        }

        $question = DB::transaction(function () use ($data) {
            return Question::create($data);
        });
        return $question;
    }
    public function updateQuestion(array $data, $id)
    {
        $question = Question::where('id', $id)->first();

        if (!$question) {
            throw new DataNotFound('Pertanyaan tidak ditemukan');
        }

        if ($data['type'] === 'Multiple Choice') {
            if (empty($data['options']) || empty($data['correct_answer'])) {
                throw new \Exception('Multiple Choice must have options and correct answer');
            }
        }

        if ($data['type'] === 'Essay' && empty($data['rubric'])) {
            throw new \Exception('Essay must have rubric');
        }

        if (isset($data['options'])) {
            $data['options'] = json_encode($data['options']);
        }

        $resultQuestion = DB::transaction(function () use ($data, $question) {
            $question->update($data);
            return $question->fresh();
        });
        return $resultQuestion;
    }
    public function deleteQuestion($id)
    {
        $question = Question::where('id', $id)->first();
        if (!$question) {
            throw new DataNotFound('Pertanyaan tidak ditemukan');
        }
        $resultQuestion = DB::transaction(function () use ($question) {
            $question->delete();
            return $question->fresh();
        });
        return $resultQuestion;
    }
}
