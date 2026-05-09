<?php

namespace App\Services;

use App\Models\Lesson;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class LessonService
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        private TeacherService $teacherService
    ) {}
    public function getAllLesson(?string $search = null): Collection
    {
        $teacher = $this->teacherService->getTeacherByUserId(Auth::id());
        
        $query = Lesson::select('id', 'name', 'teacher_id', 'class_id', 'subject_id', 'created_at', 'updated_at')
            ->where('teacher_id', $teacher->id);

        if (!empty($search)) {
            $query = SearchService::apply($query, $search, 'search_vector');
        }

        return $query->get();
    }
}
