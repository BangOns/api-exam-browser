<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Teacher extends Model
{
    protected $fillable = ['user_id', 'nip'];
    protected $table = "teachers";
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
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(
            Classes::class,       // model class
            'teacher_classes',    // pivot table
            'teacher_id',         // FK di pivot table untuk teacher
            'class_id'            // FK di pivot table untuk class
        );
    }
}
