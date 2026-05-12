<?php

namespace App\Services;

use App\Exceptions\DataNotFound;
use App\Models\Question;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class QuestionService
{
    // Batas maksimum item per halaman
    private const MAX_PER_PAGE  = 100;
    /**
     * Create a new class instance.
     */

    public function getAllQuestions(int $perPage = 5, string $search = ''): LengthAwarePaginator
    {
        // Batasi perPage agar tidak bisa di-abuse
        $perPage = min($perPage, self::MAX_PER_PAGE);

        $query = Question::select('id', 'question', 'lesson_id', 'type', 'options', 'correct_answer', 'rubric', 'max_points', 'created_at', 'updated_at')
            ->with('lesson');

        if (!empty($search)) {
            $query = SearchService::apply($query, $search, 'search_vector');
        }

        return $query->paginate($perPage);
    }
    public function getQuestionById($id)
    {
        $question = Question::where('id', $id)
            ->select('id', 'question', 'lesson_id', 'type', 'options', 'correct_answer', 'rubric', 'max_points', 'created_at', 'updated_at')
            ->with('lesson')
            ->first();
        if (!$question) {
            throw new DataNotFound('Pertanyaan tidak ditemukan');
        }
        return $question;
    }
    public function createQuestion(array $data)
    {
        $data = $this->sanitizeQuestionData($data);

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

        $data = $this->sanitizeQuestionData($data);

        $resultQuestion = DB::transaction(function () use ($data, $question) {
            $question->update($data);
            return $question->fresh();
        });
        return $resultQuestion;
    }

    /**
     * Sanitize and validate question data
     */
    private function sanitizeQuestionData(array $data): array
    {
        // Type validation
        if ($data['type'] === 'Multiple Choice') {
            if (empty($data['options']) || empty($data['correct_answer'])) {
                throw new \Exception('Multiple Choice must have options and correct answer');
            }
        }

        if ($data['type'] === 'Essay' && empty($data['rubric'])) {
            throw new \Exception('Essay must have rubric');
        }

        // Sanitize text inputs
        $data['question'] = strip_tags($data['question'], '<p><br><strong><em>');
        if (isset($data['rubric'])) {
            $data['rubric'] = strip_tags($data['rubric'], '<p><br><strong><em>');
        }

        // Encode JSON with security flags to prevent injection
        if (isset($data['options'])) {
            $data['options'] = json_encode($data['options'], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
        }

        // Sanitize correct answer
        if (isset($data['correct_answer'])) {
            $data['correct_answer'] = strip_tags($data['correct_answer']);
        }

        return $data;
    }
    public function deleteQuestion($id)
    {
        $question = Question::where('id', $id)->first();
        if (!$question) {
            throw new DataNotFound('Pertanyaan tidak ditemukan');
        }
        $resultQuestion = DB::transaction(function () use ($question) {
            $question->delete();
            return $question;
        });
        return $resultQuestion;
    }
}
