<?php

namespace App\Http\Controllers;

use App\Http\Resources\ExamToken\ExamTokenResource;
use App\Services\ExamAttemptService;
use App\Traits\ApiResponse;

class ExamTokenController extends Controller
{
    use ApiResponse;

    public function __construct(private ExamAttemptService $examAttemptService) {}

    public function generate(string $examId)
    {
        try {
            $token = $this->examAttemptService->generateNewToken($examId);
            
            return $this->successResponse(new ExamTokenResource($token), 'Token baru berhasil digenerate', 201);
        } catch (\Exception $e) {
            $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 400;
            return $this->errorResponse($e->getMessage(), $statusCode);
        }
    }
}
