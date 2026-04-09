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

            $examCreate = Exam::create($data);
            if (isset($data['questions'])) {
                $examCreate->questions()->sync($data['questions']);
            }
            return $examCreate;
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
            if (isset($data['questions'])) {
                $exam->questions()->sync($data['questions']);
            }
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

    public function monitorExam(string $id)
    {
        $exam = Exam::with(['class.students', 'attempts.student'])
            ->where('id', $id)
            ->first();

        if (!$exam) {
            throw new DataNotFound('Ujian tidak ditemukan');
        }

        $allStudents = $exam->class->students;
        $attempts = $exam->attempts;

        $attemptedStudentIds = $attempts->pluck('student_id')->toArray();

        $belumMasuk = $allStudents->whereNotIn('id', $attemptedStudentIds)->values();
        
        $sedangMengerjakan = $attempts->where('status', 'In Progress')->values();
        $selesai = $attempts->where('status', 'Submitted')->values();
        $pelanggaran = $attempts->where('exit_count', '>', 0)->values();
        $exited = $attempts->where('status', 'Exited')->values();

        return [
            'summary' => [
                'total_students' => $allStudents->count(),
                'belum_masuk_count' => $belumMasuk->count(),
                'in_progress_count' => $sedangMengerjakan->count(),
                'selesai_count' => $selesai->count(),
                'pelanggaran_count' => $pelanggaran->count(),
            ],
            'belum_masuk' => $belumMasuk,
            'sedang_mengerjakan' => $sedangMengerjakan->map(function ($attempt) {
                return [
                    'attempt_id' => $attempt->id,
                    'student' => $attempt->student,
                    'status' => $attempt->status,
                    'exit_count' => $attempt->exit_count,
                    'started_at' => $attempt->started_at,
                ];
            }),
            'selesai' => $selesai->map(function ($attempt) {
                return [
                    'attempt_id' => $attempt->id,
                    'student' => $attempt->student,
                    'status' => $attempt->status,
                    'exit_count' => $attempt->exit_count,
                    'started_at' => $attempt->started_at,
                    'submitted_at' => $attempt->submitted_at,
                ];
            }),
            'exited' => $exited->map(function ($attempt) {
                return [
                    'attempt_id' => $attempt->id,
                    'student' => $attempt->student,
                    'status' => $attempt->status,
                    'exit_count' => $attempt->exit_count,
                    'started_at' => $attempt->started_at,
                ];
            }),
            'pelanggaran' => $pelanggaran->map(function ($attempt) {
                return [
                    'attempt_id' => $attempt->id,
                    'student' => $attempt->student,
                    'status' => $attempt->status,
                    'exit_count' => $attempt->exit_count,
                ];
            }),
        ];
    }
}
