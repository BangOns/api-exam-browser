<?php

namespace App\Http\Controllers;

use App\Http\Requests\Exam\ExamRequest;
use App\Http\Resources\Exam\ExamResource;
use App\Services\ActivityLogService;
use App\Services\ExamService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ExamController extends Controller
{
    use ApiResponse;
    public function __construct(
        private ExamService $examService,
        private ActivityLogService $activityLogService
    ) {}
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $paginator = $this->examService->getAllExams($request->query('per_page', 5), $request->query('search', ''));
        return $this->successResponse(
            ExamResource::collection($paginator),
            'Data berhasil diambil',
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

    /**
     * Store a newly created resource in storage.
     */
    public function store(ExamRequest $request)
    {
        $exam = $this->examService->createExam($request->validated());

        $this->activityLogService->log($request->user(), "create", 'Exam');

        return $this->successResponse(new ExamResource($exam), 'Data berhasil ditambahkan', 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $exam = $this->examService->getExamById($id);
        return $this->successResponse(new ExamResource($exam), 'Data berhasil diambil', 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ExamRequest $request, string $id)
    {
        $exam = $this->examService->updateExam($request->validated(), $id);

        $this->activityLogService->log($request->user(), "update", 'Exam');

        return $this->successResponse(new ExamResource($exam), 'Data berhasil diupdate', 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $exam = $this->examService->getExamById($id);
        $this->examService->deleteExam($id);

        $this->activityLogService->log($request->user(), "delete", 'Exam');

        return $this->successResponse(null, 'Data berhasil dihapus', 200);
    }

    /**
     * Get monitoring data for the specified exam.
     */
    public function monitor(string $id)
    {
        $monitoringData = $this->examService->monitorExam($id);
        return $this->successResponse($monitoringData, 'Data monitoring berhasil diambil', 200);
    }
}
