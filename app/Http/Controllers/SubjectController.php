<?php

namespace App\Http\Controllers;

use App\Exceptions\DataNotFound;
use App\Http\Requests\Subject\SubjectRequest;
use App\Http\Resources\Subject\SubjectResource;
use App\Services\ActivityLogService;
use App\Services\SubjectService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    use ApiResponse;
    /**
     * Display a listing of the resource.
     */
    public function __construct(
        private SubjectService $subjectService,
        private ActivityLogService $activityLogService
    ) {}
    public function index(Request $request)
    {
        $paginator = $this->subjectService->getAllSubjects($request->query('search', ''));
        return $this->successResponse(
            SubjectResource::collection($paginator),
            'Subject retrieved successfully',
            200,
        );
    }

    public function show(string $id)
    {

        $subject = $this->subjectService->getSubjectById($id);
        if (!$subject) {
            throw new DataNotFound('Subject tidak ditemukan');
        }
        return $this->successResponse(
            new SubjectResource($subject),
            'Subject retrieved by id successfully',
            200,
        );
    }

    /**
     * POST /api/subjects
     * Buat subject baru. Hanya admin.
     */
    public function store(SubjectRequest $request)
    {
        $subject = $this->subjectService->createSubject($request->validated());

        $this->activityLogService->log($request->user(), "create", 'Subject');

        return $this->successResponse(
            new SubjectResource($subject),
            'Subject created successfully',
            201,
        );
    }

    /**
     * PUT/PATCH /api/classes/{id}
     * Update kelas. Hanya admin.
     */
    public function update(SubjectRequest $request, string $id)
    {
        $subject = $this->subjectService->updateSubject($id, $request->validated());
        if (!$subject) {
            throw new DataNotFound('Subject tidak ditemukan');
        }

        $this->activityLogService->log($request->user(), "update", 'Subject');

        return $this->successResponse(
            null,
            'Subject updated successfully',
            200,
        );
    }

    /**
     * DELETE /api/classes/{id}
     * Hapus kelas. Hanya admin.
     */
    public function destroy(Request $request, string $id)
    {
        $subject = $this->subjectService->deleteSubject($id);
        if (!$subject) {
            throw new DataNotFound('Subject tidak ditemukan');
        }

        $this->activityLogService->log($request->user(), "delete", 'Subject');

        return $this->successResponse(
            null,
            'Subject deleted successfully',
            200,
        );
    }
}
