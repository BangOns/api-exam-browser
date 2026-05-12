<?php

namespace App\Services;

use App\Models\Lesson;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
        return Lesson::with('teacher', 'class', 'subject')
            ->where('teacher_id', $teacher->id)
            ->get();
    }
}
