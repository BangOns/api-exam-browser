<?php

namespace App\Services;

use App\Exceptions\DataNotFound;
use App\Models\Exam;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class ExamService
{
    // Durasi cache dalam detik
    private const CACHE_TTL     = 60;

    // Batas maksimum item per halaman
    private const MAX_PER_PAGE  = 100;
    /**
     * Create a new class instance.
     */
    public function getAllExams(int $perPage = 5, string $search = ''): LengthAwarePaginator
    {
        // Batasi perPage agar tidak bisa di-abuse
        $perPage = min($perPage, self::MAX_PER_PAGE);


        return Exam::with('subject', 'class', 'questions')->when($search, fn($q) => $q->where('name', 'like', "%{$search}%"))
            ->paginate($perPage);
    }
    public function getExamById($id)
    {
        $exam = Exam::with('subject', 'class', 'questions')->where('id', $id)->first();
        if (!$exam) {
            throw new DataNotFound('Ujian tidak ditemukan');
        }
        return $exam;
    }
    public function createExam(array $data)
    {
        $exam = DB::transaction(function () use ($data) {
            return Exam::create($data);
        });

        $this->flushListCache();

        return $exam;
    }
    public function updateExam(array $data, $id)
    {
        $exam = Exam::where('id', $id)->first();

        if (!$exam) {
            throw new DataNotFound('Ujian tidak ditemukan');
        }
        // belum ditambahkan untuk id exam dan id question ke table exam_questions

        $resultExam = DB::transaction(function () use ($data, $exam) {
            $exam->update($data);
            return $exam->fresh();
        });

        $this->flushListCache();

        return $resultExam;
    }
    public function deleteExam($id)
    {
        $exam = Exam::where('id', $id)->first();
        if (!$exam) {
            throw new DataNotFound('Ujian tidak ditemukan');
        }
        $resultExam = DB::transaction(function () use ($exam) {
            $exam->delete();
            return $exam->fresh();
        });
        $this->flushListCache();
        return $resultExam;
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
