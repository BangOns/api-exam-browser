<?php

namespace App\Repositories\Eloquent;

use App\Models\Student;
use App\Repositories\Base\BaseRepository;

class StudentRepository extends BaseRepository
{
    public function __construct(Student $model)
    {
        parent::__construct($model);
    }

    protected function getWithRelations()
    {
        return [
            'user:id,username,full_name,role',
            'class:id,name',
        ];
    }

    protected function getSelectableColumns(): array
    {
        return [
            'id',
            'user_id',
            'class_id',
            'nisn',
            'created_at',
            'updated_at'
        ];
    }

    protected function getSearchColumns(): array
    {
        return ['nisn'];
    }
}
