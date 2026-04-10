<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ActivityLog extends Model
{
    protected $table = "activity_logs";
    protected $fillable = [
        'user_id',
        'action',
        'module',

    ];

    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = true;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->id) {
                $model->id = Str::uuid();
            }
        });
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
