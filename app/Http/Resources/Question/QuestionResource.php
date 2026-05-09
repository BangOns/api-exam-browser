<?php

namespace App\Http\Resources\Question;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
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
            "question" => e($this->question),
            "type" => $this->type,
            "options" => $this->options ? json_decode($this->options, true) : null,
            "correct_answer" => $this->correct_answer ? e($this->correct_answer) : null,
            "rubric" => $this->rubric ? e($this->rubric) : null,
            "max_points" => (int) $this->max_points,
            "lesson" => [
                "id" => $this->lesson_id ?? null,
                "class" => [
                    "id" => e($this->lesson?->class_id) ?? null,
                    "name" => e($this->lesson?->class?->name) ?? null
                ],
                "subject" => [
                    "id" => e($this->lesson?->subject_id) ?? null,
                    "name" => e($this->lesson?->subject?->name) ?? null
                ],
            ],
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
        ];
    }
}
