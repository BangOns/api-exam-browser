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
        "name",
        "lesson_id",
        "status",
        "token",
        "essay_weight",
        "pg_weight",
    ];

    protected $table = "exams";
    protected $keyType = "string";
    public $incrementing = false;

    protected $casts = [
        "essay_weight" => "integer",
        "pg_weight" => "integer",
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

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(
            Question::class,
            "exam_questions",
            "exam_id",
            "question_id",
        )->withTimestamps();
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
