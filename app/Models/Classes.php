<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Classes extends Model
{
    protected $fillable = ['name', 'level', 'department'];
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
    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }
}
