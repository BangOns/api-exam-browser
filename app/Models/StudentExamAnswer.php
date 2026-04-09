<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class StudentExamAnswer extends Model
{
    protected $table = "student_exam_answers";
    protected $keyType = "string";
    public $incrementing = false;
    protected $fillable = [
        "student_exam_attempt_id",
        "question_id",
        "answer",
        "score",
        "is_correct",
        "answered_at",
    ];
    protected $casts = [
        "answered_at" => "datetime",
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
    public function studentExamAttempt(): BelongsTo
    {
        return $this->belongsTo(StudentExamAttempt::class);
    }
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
}
