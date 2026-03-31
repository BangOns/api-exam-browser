<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Classes extends Model
{
    protected $fillable = ['nama', 'level', 'department'];
    protected $table = 'classes';
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
    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }
    public function teachers(): BelongsToMany
    {
        return $this->belongsToMany(
            Teacher::class,       // model teacher
            'teacher_classes',    // pivot table
            'class_id',           // FK di pivot table untuk class
            'teacher_id'          // FK di pivot table untuk teacher
        );
    }
}
