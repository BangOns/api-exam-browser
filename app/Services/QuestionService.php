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

        $question = DB::transaction(function () use ($data) {
            return Question::create($data);
        });
        $this->flushListCache();
        return $question;
    }
    public function updateQuestion(array $data, $id)
    {
        $question = Question::where('id', $id)->first();

        if (!$question) {
            throw new DataNotFound('Pertanyaan tidak ditemukan');
        }

        $resultQuestion = DB::transaction(function () use ($data, $question) {
            return $question->update($data);
        });
        $this->flushListCache();
        return $resultQuestion;
    }
    public function deleteQuestion($id)
    {
        $question = Question::where('id', $id)->first();
        if (!$question) {
            throw new DataNotFound('Pertanyaan tidak ditemukan');
        }
        $resultQuestion = DB::transaction(function () use ($question) {
            return $question->delete();
        });
        $this->flushListCache();
        return $resultQuestion;
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
