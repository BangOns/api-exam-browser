<?php

namespace App\Http\Resources\ExamAttempt;

use App\Http\Resources\ExamAnswer\ExamAnswerResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamAttemptResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'exit_count' => $this->exit_count,
            'started_at' => $this->started_at,
            'answers' => ExamAnswerResource::collection($this->whenLoaded('answers')),
            'submitted_at' => $this->submitted_at,
            'total_score' => $this->total_score,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
