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
        return [
            "id" => $this->id, // null jika belum dijawab
            "answer" => $this->answer,
            "score" => $this->score,
            "graded_by" => $this->graded_by,
            "graded_at" => $this->graded_at,
            "is_correct" => $this->is_correct,
            "answered_at" => $this->answered_at,
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
            ],
        ];
    }
}
