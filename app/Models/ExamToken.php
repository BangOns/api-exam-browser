<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ExamToken extends Model
{
    protected $table = 'exam_tokens';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'exam_id',
        'token',
        'is_active',
        'expired_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'expired_at' => 'datetime',
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
}
