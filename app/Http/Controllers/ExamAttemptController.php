<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExamAttempt\EnterExamRequest;
use App\Http\Resources\ExamAttempt\ExamAttemptResource;
use App\Services\ExamAttemptService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ExamAttemptController extends Controller
{
    use ApiResponse;

    public function __construct(private ExamAttemptService $examAttemptService) {}

    public function enter(EnterExamRequest $request, string $examId)
    {
        try {
            $studentId = $request->user()->student->id;
            
            $attempt = $this->examAttemptService->enterExam(
                $studentId,
                $examId,
                $request->validated('token')
            );

            return $this->successResponse(new ExamAttemptResource($attempt), 'Berhasil memasuki ujian', 200);
        } catch (\Exception $e) {
            $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 400;
            return $this->errorResponse($e->getMessage(), $statusCode);
        }
    }

    public function exit(Request $request, string $examId)
    {
        try {
            $studentId = $request->user()->student->id;
            
            $attempt = $this->examAttemptService->exitExam($studentId, $examId);

            return $this->successResponse(new ExamAttemptResource($attempt), 'Berhasil keluar dari ujian (status disimpan)', 200);
        } catch (\Exception $e) {
            $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 400;
            return $this->errorResponse($e->getMessage(), $statusCode);
        }
    }

    public function submit(Request $request, string $examId)
    {
        try {
            $studentId = $request->user()->student->id;
            
            $attempt = $this->examAttemptService->submitExam($studentId, $examId);

            return $this->successResponse(new ExamAttemptResource($attempt), 'Ujian berhasil disubmit', 200);
        } catch (\Exception $e) {
            $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 400;
            return $this->errorResponse($e->getMessage(), $statusCode);
        }
    }
}
