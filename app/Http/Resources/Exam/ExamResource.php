<?php

namespace App\Http\Resources\Exam;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "name" => e($this->name),
            "lesson" => [
                "id" => $this->lesson?->id ?? null,
                "class" => [
                    "id" => $this->lesson?->class?->id ?? null,
                    "name" => $this->lesson?->class?->name ?? null,
                ],
                "subject" => [
                    "id" => $this->lesson?->subject?->id ?? null,
                    "name" => $this->lesson?->subject?->name ?? null,
                ],
            ],
            "status" => $this->status,
            "questions" => $this->questions->map(function ($question) {
                return [
                    "id" => $question->id,
                    "type" => $question->type,
                    "question" => e($question->question),
                    "options" => $question->options
                        ? json_decode($question->options, true)
                        : [],
                    "answer" => $question->correct_answer
                        ? e($question->correct_answer)
                        : null,
                    "rubric" => $question->rubric
                        ? e($question->rubric)
                        : null,
                ];
            }),
        ];
    }
}
