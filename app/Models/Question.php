<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Question extends Model
{
    protected $fillable = [
        'question',
        'lesson_id',
        'type',
        'options',
        'correct_answer',
        'rubric',
        'max_points',
    ];
    protected $table  = "questions";
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
    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }
}
