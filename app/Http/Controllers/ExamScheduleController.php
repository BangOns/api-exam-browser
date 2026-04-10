<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExamSchedule\ExamScheduleRequest;
use App\Http\Resources\ExamSchedule\ExamScheduleResource;
use App\Services\ActivityLogService;
use App\Services\ExamScheduleService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExamScheduleController extends Controller
{
    use ApiResponse;

    public function __construct(
        private ExamScheduleService $examScheduleService,
        private ActivityLogService $activityLogService
    ) {}

    /**
     * Display a listing of exam schedules.
     */
    public function index(Request $request): JsonResponse
    {
        $paginator = $this->examScheduleService->getAllSchedules(
            $request->integer('per_page', 10),
            $request->string('search', '')->toString()
        );

        return $this->successResponse(
            ExamScheduleResource::collection($paginator),
            'Data berhasil diambil',
            200,
            [
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page'    => $paginator->lastPage(),
                    'per_page'     => $paginator->perPage(),
                    'total'        => $paginator->total(),
                ],
            ]
        );
    }

    /**
     * Store a newly created exam schedule.
     */
    public function store(ExamScheduleRequest $request): JsonResponse
    {
        $schedule = $this->examScheduleService->createSchedule($request->validated());

        $this->activityLogService->log($request->user(), "Created exam schedule for: {$schedule->exam->name}", 'Exam Schedule');

        return $this->successResponse(
            new ExamScheduleResource($schedule->load('exam')),
            'Jadwal ujian berhasil ditambahkan',
            201
        );
    }

    /**
     * Display the specified exam schedule.
     */
    public function show(string $id): JsonResponse
    {
        $schedule = $this->examScheduleService->getScheduleById($id);

        return $this->successResponse(
            new ExamScheduleResource($schedule),
            'Data berhasil diambil',
            200
        );
    }

    /**
     * Update the specified exam schedule.
     */
    public function update(ExamScheduleRequest $request, string $id): JsonResponse
    {
        $schedule = $this->examScheduleService->updateSchedule($request->validated(), $id);

        $this->activityLogService->log($request->user(), "Updated exam schedule for: {$schedule->exam->name}", 'Exam Schedule');

        return $this->successResponse(
            new ExamScheduleResource($schedule),
            'Jadwal ujian berhasil diupdate',
            200
        );
    }

    /**
     * Remove the specified exam schedule.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $schedule = $this->examScheduleService->getScheduleById($id);
        $this->examScheduleService->deleteSchedule($id);

        $this->activityLogService->log($request->user(), "delete", 'Exam Schedule');

        return $this->successResponse(
            null,
            'Jadwal ujian berhasil dihapus',
            200
        );
    }
}
