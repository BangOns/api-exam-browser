<?php

namespace App\Http\Controllers;

use App\Http\Resources\ExamToken\ExamTokenResource;
use App\Services\ActivityLogService;
use App\Services\ExamAttemptService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ExamTokenController extends Controller
{
    use ApiResponse;

    public function __construct(
        private ExamAttemptService $examAttemptService,
        private ActivityLogService $activityLogService
    ) {}

    public function generate(Request $request, string $examId)
    {
        try {
            $token = $this->examAttemptService->generateNewToken($examId);

            $this->activityLogService->log($request->user(), "generate", 'Exam Token');

            return $this->successResponse(new ExamTokenResource($token), 'Token baru berhasil digenerate', 201);
        } catch (\Exception $e) {
            $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 400;
            return $this->errorResponse($e->getMessage(), $statusCode);
        }
    }
}
