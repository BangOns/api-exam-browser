<?php

namespace App\Http\Controllers;

use App\Exceptions\DataNotFound;
use App\Http\Requests\Class\ClassRequest;
use App\Http\Requests\Teacher\TeacherRequest;
use App\Http\Requests\Teacher\TeacherRequestUpdate;
use App\Http\Resources\Teacher\TeacherResource;
use App\Services\ActivityLogService;
use App\Services\TeacherService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    use ApiResponse;
    /**
     * Display a listing of the resource.
     */
    public function __construct(
        private TeacherService $teacherService,
        private ActivityLogService $activityLogService
    ) {}
    public function index(Request $request)
    {
        $paginator = $this->teacherService->getAllTeachers(5, $request->query('search', ''));
        return $this->successResponse(
            TeacherResource::collection($paginator),
            'Teacher retrieved successfully',
            200,
            [
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ]
            ]
        );
    }

    public function show(string $id)
    {

        $teacher = $this->teacherService->getTeacherById($id);
        if (!$teacher) {
            throw new DataNotFound('Teacher tidak ditemukan');
        }
        return $this->successResponse(
            new TeacherResource($teacher),
            'Kelas retrieved by id successfully',
            200,
        );
    }

    /**
     * POST /api/classes
     * Buat kelas baru. Hanya admin.
     */
    public function store(TeacherRequest $request)
    {
        $teacher = $this->teacherService->createTeacher($request->validated());

        $this->activityLogService->log($request->user(), "create", 'Teacher');

        return $this->successResponse(
            new TeacherResource($teacher),
            'Teacher created successfully',
            201,
        );
    }

    /**
     * PUT/PATCH /api/classes/{id}
     * Update kelas. Hanya admin.
     */
    public function update(TeacherRequestUpdate $request, string $id)
    {
        $teacher = $this->teacherService->updateTeacher($id, $request->validated());
        if (!$teacher) {
            throw new DataNotFound('Teacher tidak ditemukan');
        }

        $this->activityLogService->log($request->user(), "update", 'Teacher');

        return $this->successResponse(
            null,
            'Teacher updated successfully',
            200,
        );
    }

    /**
     * DELETE /api/classes/{id}
     * Hapus kelas. Hanya admin.
     */
    public function destroy(Request $request, string $id)
    {
        $teacher = $this->teacherService->deleteTeacher($id);
        if (!$teacher) {
            throw new DataNotFound('Teacher tidak ditemukan');
        }

        $this->activityLogService->log($request->user(), "delete", 'Teacher');

        return $this->successResponse(
            null,
            'Teacher deleted successfully',
            200,
        );
    }
}
