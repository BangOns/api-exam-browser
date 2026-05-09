<?php

namespace App\Repositories\Eloquent;

use App\Models\Exam;
use App\Repositories\Base\BaseRepository;

class ExamRepository extends BaseRepository
{
    public function __construct(Exam $model)
    {
        parent::__construct($model);
    }

    protected function getWithRelations()
    {
        return [
            'subject:id,name',
            'class:id,name',
        ];
    }

    protected function getSelectableColumns(): array
    {
        return [
            'id', 
            'name', 
            'subject_id', 
            'class_id', 
            'status', 
            'created_at', 
            'updated_at'
        ];
    }

    protected function getSearchColumns(): array
    {
        return ['name', 'status'];
    }

    /**
     * Get exam with questions
     */
    public function findWithQuestions(string $id)
    {
        return $this->model
            ->select($this->getSelectableColumns())
            ->with([
                'subject:id,name',
                'class:id,name',
                'questions:id,question,type,lesson_id,options,correct_answer,rubric,max_points'
            ])
            ->findOrFail($id);
    }
}
