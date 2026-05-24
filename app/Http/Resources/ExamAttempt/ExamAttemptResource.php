<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamAttemptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "student_id" => $this->student_id,
            "nama" => $this->student->user->full_name,
            "nisn" => $this->student->nisn,
            "kelas" => $this->student->class->name ?? null,
            "exam_id" => $this->exam_id,
            "exam_name" => $this->exam->name,
            "status" => $this->status,
            "exit_count" => $this->exit_count,
            "total_score" => $this->total_score,
            "started_at" => $this->started_at?->format("d M Y, H:i"),
            "submitted_at" => $this->submitted_at?->format("d M Y, H:i"),
            "last_activity_at" => $this->last_activity_at?->format("d M Y, H:i"),
        ];
    }
}
