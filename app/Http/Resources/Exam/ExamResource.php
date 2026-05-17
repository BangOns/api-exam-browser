<?php

namespace App\Http\Resources\Exam;

use App\Http\Resources\ExamSchedule\ExamScheduleResource;
use App\Http\Resources\ExamToken\ExamTokenResource;
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
            "schedule" => $this->whenLoaded("schedules", function () {
                return $this->schedules->last()
                    ? new ExamScheduleResource($this->schedules->last())
                    : null;
            }),
            "token" => $this->whenLoaded("tokens", function () {
                return $this->tokens->last()
                    ? new ExamTokenResource($this->tokens->last())
                    : null;
            }),
            "questions" => $this->questions->map(function ($question) {
                return [
                    "id" => $question->id,
                    "type" => $question->type,
                    "question" => $question->question,
                    "options" => $question->options
                        ? json_decode($question->options, true)
                        : [],
                    "answer" => $question->correct_answer
                        ? $question->correct_answer
                        : null,
                    "rubric" => $question->rubric ? $question->rubric : null,
                ];
            }),
        ];
    }
}
