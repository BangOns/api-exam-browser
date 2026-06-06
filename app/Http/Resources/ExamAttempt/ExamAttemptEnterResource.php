<?php

namespace App\Http\Resources\ExamAttempt;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamAttemptEnterResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "status" => $this->status,
            "started_at" => $this->started_at,
            "exit_count" => $this->exit_count,
            "security_config" => $this->security_config,
        ];
    }
}
