<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExamAttempt\EnterExamRequest;
use App\Http\Resources\ExamAttempt\ExamAttemptResource;
use App\Services\ActivityLogService;
use App\Services\ExamAttemptService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ExamAttemptController extends Controller
{
    use ApiResponse;

    public function __construct(
        private ExamAttemptService $examAttemptService,
        private ActivityLogService $activityLogService
    ) {}

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
        } finally {
            if (isset($attempt)) {
                $this->activityLogService->log($request->user(), "enter", 'Exam Attempt');
            }
        }
    }

    public function exit(Request $request, string $examId)
    {
        try {
            $type = $request->input('type') ?? null;
            $studentId = $request->user()->student->id;

            $attempt = $this->examAttemptService->exitExam($studentId, $examId, $type);

            return $this->successResponse(new ExamAttemptResource($attempt), 'Berhasil keluar dari ujian (status disimpan)', 200);
        } catch (\Exception $e) {
            $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 400;
            return $this->errorResponse($e->getMessage(), $statusCode);
        } finally {
            if (isset($attempt)) {
                $this->activityLogService->log($request->user(), "exit", 'Exam Attempt');
            }
        }
    }

    public function submit(Request $request, string $examId)
    {
        try {
            $validated = $request->validate([
                'answers' => 'nullable|array',
                'answers.*.question_id' => 'required|string',
                'answers.*.answer' => 'required|string',
            ]);

            $studentId = $request->user()->student->id;

            $attempt = $this->examAttemptService->submitExam($studentId, $examId, $validated['answers'] ?? []);

            return $this->successResponse(new ExamAttemptResource($attempt), 'Ujian berhasil disubmit', 200);
        } catch (\Exception $e) {
            $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 400;
            return $this->errorResponse($e->getMessage(), $statusCode);
        } finally {
            if (isset($attempt)) {
                $this->activityLogService->log($request->user(), "submit", 'Exam Attempt');
            }
        }
    }
}
