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
            "id" => $this->id,
            "answer" => $this->answer,
            "score" => $this->score,
            "is_correct" => $this->is_correct,
            "answered_at" => $this->answered_at,
        ];
    }
}
