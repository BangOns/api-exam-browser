<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Question extends Model
{
    protected $table = "questions";
    protected $keyType = "string";
    public $incrementing = false;

    protected $fillable = [
        "question",
        "lesson_id",
        "type",
        "options",
        "correct_answer",
        "rubric",
    ];

    protected $casts = [
        "options" => "array",
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
}
