<?php

namespace App\Http\Resources\ExamAttempt;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamAttemptResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "nama" => $this->student->user->full_name,
            "nisn" => $this->student->nisn,
            "kelas" => $this->student->class->name, // sesuaikan kolom di tabel classes
            "attempts" => $this->student->examAttempts->map(function (
                $attempt,
            ) {
                return [
                    "id" => $attempt->id,
                    "exam" => $attempt->exam->name,
                    "status" => $attempt->status,
                    "exit_count" => $attempt->exit_count,
                    "total_score" => $attempt->total_score,
                    "started_at" => $attempt->started_at?->format("d M Y, H:i"),
                    "submitted_at" => $attempt->submitted_at?->format(
                        "d M Y, H:i",
                    ),
                    "last_activity_at" => $attempt->last_activity_at?->format(
                        "d M Y, H:i",
                    ),
                ];
            }),
        ];
    }
}
