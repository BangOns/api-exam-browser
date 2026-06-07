<?php

namespace App\Http\Resources\ExamAttempt;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamAttemptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // $examId = $request->route("examId");
        return [
            "id" => $this->id,
            "nama" => $this->student->user->full_name,
            "nisn" => $this->student->nisn,
            "kelas" => $this->student->class->name, // sesuaikan kolom di tabel classes
            "attempts" => [
                "id" => $this->exam->id,
                "exam" => $this->exam->name,
                "status" => $this->status,
                "exit_count" => $this->exit_count,
                "total_score" => $this->total_score,
                "started_at" => $this->started_at?->format("d M Y, H:i"),
                "submitted_at" => $this->submitted_at?->format("d M Y, H:i"),
                "last_activity_at" => $this->last_activity_at?->format(
                    "d M Y, H:i",
                ),
            ],
        ];
    }
}
