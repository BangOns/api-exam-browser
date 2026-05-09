<?php

namespace App\Repositories\Eloquent;

use App\Models\Question;
use App\Repositories\Base\BaseRepository;

class QuestionRepository extends BaseRepository
{
    public function __construct(Question $model)
    {
        parent::__construct($model);
    }

    protected function getWithRelations()
    {
        return [
            'lesson:id,name,subject_id,class_id',
            'lesson.subject:id,name',
            'lesson.class:id,name',
        ];
    }

    protected function getSelectableColumns(): array
    {
        return [
            'id',
            'question',
            'lesson_id',
            'type',
            'options',
            'correct_answer',
            'rubric',
            'max_points',
            'created_at',
            'updated_at'
        ];
    }

    protected function getSearchColumns(): array
    {
        return ['question', 'type'];
    }
}
