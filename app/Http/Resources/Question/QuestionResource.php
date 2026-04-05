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
            "question" => $this->question,
            "type" => $this->type,
            "options" => $this->options ? json_decode($this->options) : null,
            "correct_answer" => $this->correct_answer ? json_decode($this->correct_answer) : null,
            "rubric" => $this->rubric ? json_decode($this->rubric) : null,
            "max_points" => $this->max_points,
            "class" => $this->lesson->class->name,
            "subject" => $this->lesson->subject->name,
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
        ];
    }
}
