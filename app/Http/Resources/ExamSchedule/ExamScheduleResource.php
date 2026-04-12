<?php

namespace App\Http\Resources\ExamSchedule;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamScheduleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'exam'       => $this->whenLoaded('exam', fn() => [
                // 'id'   => $this->exam->id,
                'name' => $this->exam->name,
                'subject' => $this->exam->subject?->name,
                'class' => $this->exam->class?->name,

            ]),
            'exam_date'  => $this->exam_date?->format('Y-m-d'),
            'start_time' => $this->start_time,
            'end_time'   => $this->end_time,
            'duration'   => $this->duration,  // dalam menit
            'status'     => $this->status,

        ];
    }
}
