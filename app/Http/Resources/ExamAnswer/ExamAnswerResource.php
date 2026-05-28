<?php

namespace App\Http\Resources\ExamAnswer;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamAnswerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // dd($this->studentExamAttempt->student);
        return [
            "id" => $this->id,
            "answer" => $this->answer,
            "score" => $this->score,
            "student" => [
                "id" => $this->studentExamAttempt->student->id,
                "name" => $this->studentExamAttempt->student->user->full_name,
                "class" => $this->studentExamAttempt->student->class->name,
            ],
            "exam" => [
                "id" => $this->studentExamAttempt->exam->id,
                "name" => $this->studentExamAttempt->exam->name,
            ],
            "question" => [
                "id" => $this->question->id,
                "question" => $this->question->question,
                "lesson_id" => $this->question->lesson_id,
                "type" => $this->question->type,
                "options" => $this->question->options,
                "correct_answer" => $this->question->correct_answer,
                "rubric" => $this->question->rubric,
                "max_points" => $this->question->max_points,
            ],
            "is_correct" => $this->is_correct,
            "answered_at" => $this->answered_at,
        ];
    }
}
