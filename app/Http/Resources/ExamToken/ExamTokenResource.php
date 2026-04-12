<?php

namespace App\Http\Resources\ExamToken;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamTokenResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'token' => $this->token,
            'is_active' => $this->is_active,
            'expired_at' => $this->expired_at,
        ];
    }
}
