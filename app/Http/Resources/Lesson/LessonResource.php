<?php

namespace App\Http\Resources\Lesson;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonResource extends JsonResource
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
            "class" => [
                "id" => $this->class->id ?? null,
                "name" => $this->class?->name ?? null
            ],
            "subject" => [
                "id" => $this->subject->id ?? null,
                "name" => $this->subject?->name ?? null
            ],
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at
        ];
    }
}
