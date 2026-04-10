<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ExamViolation extends Model
{
    protected $table = 'exam_violations';
    protected $fillable = [
        'attempt_id',
        'type',
    ];

    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->id) {
                $model->id = Str::uuid();
            }
        });
    }

    public function attempt()
    {
        return $this->belongsTo(StudentExamAttempt::class);
    }
}
