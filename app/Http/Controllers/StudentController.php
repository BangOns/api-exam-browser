<?php

namespace App\Http\Controllers;

use App\Exceptions\DataNotFound;
use App\Http\Requests\Student\StudentRequest;
use App\Http\Requests\Student\StudentRequestUpdate;
use App\Http\Resources\Student\StudentResource;
use App\Services\ActivityLogService;
use App\Services\StudentService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class StudentController extends Controller
{
    use ApiResponse;
    /**
     * Display a listing of the resource.
     */
    public function __construct(
        private StudentService $studentService,
        private ActivityLogService $activityLogService
    ) {}
    public function index(Request $request)
    {
        $paginator = $this->studentService->getAllStudents(5, $request->query('search', ''));
        return $this->successResponse(
            StudentResource::collection($paginator),
            'Student retrieved successfully',
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

        $student = $this->studentService->getStudentById($id);
        if (!$student) {
            throw new DataNotFound('Student tidak ditemukan');
        }
        return $this->successResponse(
            new StudentResource($student),
            'Student retrieved by id successfully',
            200,
        );
    }

    /**
     * POST /api/classes
     * Buat kelas baru. Hanya admin.
     */
    public function store(StudentRequest $request)
    {
        $student = $this->studentService->createStudent($request->validated());

        $this->activityLogService->log($request->user(), "create", 'Student');

        return $this->successResponse(
            new StudentResource($student),
            'Student created successfully',
            201,
        );
    }

    /**
     * PUT/PATCH /api/classes/{id}
     * Update kelas. Hanya admin.
     */
    public function update(StudentRequestUpdate $request, string $id)
    {
        $student = $this->studentService->updateStudent($id, $request->validated());
        if (!$student) {
            throw new DataNotFound('Student tidak ditemukan');
        }

        $this->activityLogService->log($request->user(), "update", 'Student');

        return $this->successResponse(
            null,
            'Student updated successfully',
            200,
        );
    }

    /**
     * DELETE /api/classes/{id}
     * Hapus kelas. Hanya admin.
     */
    public function destroy(Request $request, string $id)
    {
        $student = $this->studentService->deleteStudent($id);
        if (!$student) {
            throw new DataNotFound('Student tidak ditemukan');
        }

        $this->activityLogService->log($request->user(), "delete", 'Student');

        return $this->successResponse(
            null,
            'Student deleted successfully',
            200,
        );
    }
}
