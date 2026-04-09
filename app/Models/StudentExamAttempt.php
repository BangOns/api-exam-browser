<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class StudentExamAttempt extends Model
{
    protected $table = 'student_exam_attempts';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'exam_id',
        'student_id',
        'status',
        'exit_count',
        'started_at',
        'submitted_at',
        'total_score',

    ];

    protected $casts = [
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
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

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
    public function answers(): HasMany
    {
        return $this->hasMany(StudentExamAnswer::class);
    }
}
