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
            "name" => $this->name,
            "subject" => $this->subject?->name ?? null,
            "class" => $this->class?->name ?? null,
            "status" => $this->status,
            "questions" => $this->questions->map(function ($question) {
                return [
                    "id" => $question->id,
                    "type" => $question->type,
                    "question" => $question->question,
                    "options" => $question->options ? json_decode($question->options) : [],
                    "answer" => $question->correct_answer ?? null,
                    "rubric" => $question->rubric ?? null,

                ];
            }),

        ];
    }
}
