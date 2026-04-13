<?php

namespace App\Http\Resources\Teacher;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TeacherResource extends JsonResource
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
            "name" => $this->user->full_name,
            "nip" => $this->nip,
            'teaching_assignments' => $this->lessons->map(function ($lesson) {
                return [
                    'class_id' => $lesson->class->id,
                    'class_name' => $lesson->class->name,
                    'subject_id' => $lesson->subject->id,
                    'subject_name' => $lesson->subject->name,
                ];
            }),
            "created_at" => $this->created_at,
            "updated_at" => $this->updated_at,
        ];
    }
}
