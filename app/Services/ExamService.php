<?php

namespace App\Services;

use App\Exceptions\DataNotFound;
use App\Models\Exam;
use App\Models\Student;
use App\Models\StudentExamAttempt;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ExamService
{

    // Batas maksimum item per halaman
    private const MAX_PER_PAGE  = 100;
    /**
     * Create a new class instance.
     */
    public function getAllExams(int $perPage = 5, string $search = ''): LengthAwarePaginator
    {
        // Batasi perPage agar tidak bisa di-abuse
        $perPage = min($perPage, self::MAX_PER_PAGE);

        $query = Exam::select('id', 'name', 'lesson_id', 'status', 'created_at', 'updated_at')
            ->with('lesson', 'schedules', 'tokens');

        if (!empty($search)) {
            $query = SearchService::apply($query, $search, 'search_vector');
        }

        return $query->paginate($perPage);
    }
    public function getExamById($id)
    {
        $exam = Exam::where('id', $id)
            ->with('lesson')
            ->with(['questions' => function ($query) {
                $query->select('id', 'question', 'type', 'lesson_id', 'options', 'correct_answer', 'rubric', 'max_points');
            }])
            ->select('id', 'name', 'status', 'lesson_id', 'created_at', 'updated_at')
            ->first();
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

        return $resultExam;
    }
    public function deleteExam($id)
    {
        $exam = Exam::where('id', $id)->first();
        if (!$exam) {
            throw new DataNotFound('Ujian tidak ditemukan');
        }
        DB::transaction(function () use ($exam) {
            $exam->delete();
        });
        return $exam;
    }

    public function monitorExam(string $id)
    {
        $exam = Exam::where('id', $id)->first();

        if (!$exam) {
            throw new DataNotFound('Ujian tidak ditemukan');
        }

        $totalStudents = Student::where('class_id', $exam->class_id)->count();

        $belumMasuk = Student::where('class_id', $exam->class_id)
            ->whereNotIn('id', function ($query) use ($id) {
                $query->select('student_id')
                    ->from('student_exam_attempts')
                    ->where('exam_id', $id);
            })
            ->get();

        // ambil data attempt dengan eager loading relasi student
        $sedangMengerjakan = $this->getAttemptsByStatus($id, 'In Progress', ['started_at']);
        $selesai = $this->getAttemptsByStatus($id, 'Submitted', ['started_at', 'submitted_at']);
        $exited = $this->getAttemptsByStatus($id, 'Exited', ['started_at']);
        $pelanggaran = $this->getViolationAttempts($id);

        return [
            'summary' => [
                'total_students' => $totalStudents,
                'belum_masuk_count' => $belumMasuk->count(),
                'in_progress_count' => $sedangMengerjakan->count(),
                'selesai_count' => $selesai->count(),
                'pelanggaran_count' => $pelanggaran->count(),
            ],
            'belum_masuk' => $belumMasuk,
            'sedang_mengerjakan' => $sedangMengerjakan,
            'selesai' => $selesai,
            'exited' => $exited,
            'pelanggaran' => $pelanggaran,
        ];
    }

    /**
     * Get exam attempts by status
     */
    private function getAttemptsByStatus(string $examId, string $status, array $extraFields = []): \Illuminate\Support\Collection
    {
        $baseFields = ['id', 'student_id', 'status', 'exit_count', 'started_at'];
        $selectFields = array_unique(array_merge($baseFields, $extraFields));

        return StudentExamAttempt::where('exam_id', $examId)
            ->where('status', $status)
            ->with('student:id,nip,user_id')
            ->select($selectFields)
            ->get()
            ->map(fn($attempt) => $this->mapAttemptToArray($attempt, $extraFields));
    }

    /**
     * Get violation attempts (exit_count > 0)
     */
    private function getViolationAttempts(string $examId): \Illuminate\Support\Collection
    {
        return StudentExamAttempt::where('exam_id', $examId)
            ->where('exit_count', '>', 0)
            ->with('student:id,nip,user_id')
            ->select('id', 'student_id', 'status', 'exit_count')
            ->get()
            ->map(fn($attempt) => $this->mapAttemptToArray($attempt));
    }

    /**
     * Map attempt to array format
     */
    private function mapAttemptToArray($attempt, array $extraFields = []): array
    {
        $mapped = [
            'attempt_id' => $attempt->id,
            'student' => $attempt->student,
            'status' => $attempt->status,
            'exit_count' => $attempt->exit_count,
        ];

        // Add starting time if available
        if (isset($attempt->started_at)) {
            $mapped['started_at'] = $attempt->started_at;
        }

        // Add extra fields if present
        foreach ($extraFields as $field) {
            if ($field !== 'started_at' && isset($attempt->$field)) {
                $mapped[$field] = $attempt->$field;
            }
        }

        return $mapped;
    }
}
