<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Exam extends Model
{
    protected $fillable = [
        'name',
        'subject_id',
        'class_id',
        'status',
        'token',
    ];
    protected $table = 'exams';
    protected $keyType = 'string';
    public $incrementing = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->id) {
                $model->id = Str::uuid();
            }
        });
    }


    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }
    public function class(): BelongsTo
    {
        return $this->belongsTo(Classes::class);
    }
    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'exam_questions', 'exam_id', 'question_id');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(ExamSchedule::class);
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(ExamToken::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(StudentExamAttempt::class);
    }
}
