<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamSchedule extends Model
{
    use HasUuids;

    protected $table = 'exam_schedules';

    protected $fillable = [
        'exam_id',
        'exam_date',
        'start_time',
        'end_time',
        'duration',
        'status',
    ];

    protected $casts = [
        'exam_date' => 'date:Y-m-d',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }
}
