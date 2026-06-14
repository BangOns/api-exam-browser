<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ExamAnswerScoreLog extends Model
{
    protected $table = "exam_answer_score_logs";
    protected $keyType = "string";
    public $incrementing = false;

    protected $fillable = [
        "id",
        "student_exam_answer_id",
        "attempt_id",
        "question_id",
        "graded_by",
        "score_before",
        "score_after",
        "source",
    ];

    protected $casts = [
        "score_before" => "integer",
        "score_after" => "integer",
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (!$model->id) {
                $model->id = Str::uuid();
            }
        });
    }

    public function studentExamAnswer(): BelongsTo
    {
        return $this->belongsTo(StudentExamAnswer::class);
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(StudentExamAttempt::class, "attempt_id");
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function gradedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, "graded_by");
    }
}
