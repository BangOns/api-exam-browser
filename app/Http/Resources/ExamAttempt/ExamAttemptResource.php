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
            "nama" => $this->user->name,
            "nisn" => $this->nisn,
            "kelas" => $this->class->name, // sesuaikan kolom di tabel classes

            "attempts" => $this->examAttempts->map(
                fn($attempt) => [
                    "id" => $attempt->id,
                    "exam" => $attempt->exam->title,
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
                ],
            ),
        ];
    }
}
