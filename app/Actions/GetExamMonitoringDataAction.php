<?php

namespace App\Actions;

use App\Models\Exam;
use App\Models\Student;
use App\Models\StudentExamAttempt;
use App\Repositories\Eloquent\StudentRepository;

class GetExamMonitoringDataAction
{
    public function __construct(
        private StudentRepository $studentRepository
    ) {}

    /**
     * Get complete monitoring data for an exam
     * Optimized to avoid N+1 queries and PHP filtering
     */
    public function execute(string $examId): array
    {
        $exam = Exam::findOrFail($examId);

        $totalStudents = Student::where('class_id', $exam->class_id)->count();

        $belumMasuk = Student::where('class_id', $exam->class_id)
            ->whereNotIn('id', function ($query) use ($examId) {
                $query->select('student_id')
                    ->from('student_exam_attempts')
                    ->where('exam_id', $examId);
            })
            ->get();

        $sedangMengerjakan = StudentExamAttempt::where('exam_id', $examId)
            ->where('status', 'In Progress')
            ->with('student:id,nip,user_id')
            ->select('id', 'student_id', 'status', 'exit_count', 'started_at')
            ->get()
            ->map($this->mapAttempt());

        $selesai = StudentExamAttempt::where('exam_id', $examId)
            ->where('status', 'Submitted')
            ->with('student:id,nip,user_id')
            ->select('id', 'student_id', 'status', 'exit_count', 'started_at', 'submitted_at')
            ->get()
            ->map($this->mapAttempt('submitted_at'));

        $exited = StudentExamAttempt::where('exam_id', $examId)
            ->where('status', 'Exited')
            ->with('student:id,nip,user_id')
            ->select('id', 'student_id', 'status', 'exit_count', 'started_at')
            ->get()
            ->map($this->mapAttempt());

        $pelanggaran = StudentExamAttempt::where('exam_id', $examId)
            ->where('exit_count', '>', 0)
            ->with('student:id,nip,user_id')
            ->select('id', 'student_id', 'status', 'exit_count')
            ->get()
            ->map($this->mapAttempt());

        return [
            'summary' => [
                'total_students' => $totalStudents,
                'belum_masuk_count' => count($belumMasuk),
                'in_progress_count' => count($sedangMengerjakan),
                'selesai_count' => count($selesai),
                'pelanggaran_count' => count($pelanggaran),
            ],
            'belum_masuk' => $belumMasuk,
            'sedang_mengerjakan' => $sedangMengerjakan,
            'selesai' => $selesai,
            'exited' => $exited,
            'pelanggaran' => $pelanggaran,
        ];
    }

    /**
     * Map StudentExamAttempt to array with optional extra fields
     * DRY: Consolidated from 3 identical methods
     * 
     * @param string|null $extraField Optional additional field to include (e.g. 'submitted_at')
     */
    private function mapAttempt(?string $extraField = null)
    {
        return fn($attempt) => array_merge(
            [
                'attempt_id' => $attempt->id,
                'student' => $attempt->student,
                'status' => $attempt->status,
                'exit_count' => $attempt->exit_count,
                'started_at' => $attempt->started_at ?? null,
            ],
            $extraField && isset($attempt->$extraField) 
                ? [$extraField => $attempt->$extraField] 
                : []
        );
    }
}
