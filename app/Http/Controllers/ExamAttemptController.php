<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExamAttempt\EnterExamRequest;
use App\Http\Requests\ExamAttempt\SubmitExamRequest;
use App\Http\Resources\ExamAttempt\ExamAttemptEnterResource;
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
        private ActivityLogService $activityLogService,
    ) {}

    public function index(Request $request, string $examId)
    {
        $attempts = $this->examAttemptService->getAllExamAttempts(
            $request->perPage ?? 5,
            $request->search ?? "",
            $examId,
        );
        return $this->successResponse(
            ExamAttemptResource::collection($attempts),
            "Berhasil mengambil daftar ujian",
            200,
        );
    }
    public function indexByIdTeacher(Request $request, string $examId)
    {
        $teacher = $request->user()->teacher;
        $attempts = $this->examAttemptService->getAllExamAttemptsByIdTeacher(
            $request->perPage ?? 5,
            $request->search ?? "",
            $examId,
            $teacher->id,
        );
        return $this->successResponse(
            ExamAttemptResource::collection($attempts),
            "Berhasil mengambil daftar ujian",
            200,
        );
    }

    public function enter(EnterExamRequest $request, string $examId)
    {
        try {
            if ($request->user()->role !== "student") {
                throw new \Exception("Hanya siswa yang dapat memasuki ujian");
            }
            $studentId = $request->user()->student->id;
            $attempt = $this->examAttemptService->enterExam(
                $studentId,
                $examId,
                $request->validated("token"),
            );

            $this->activityLogService->log(
                $request->user(),
                "enter",
                "Exam Attempt",
            );

            return $this->successResponse(
                new ExamAttemptEnterResource($attempt),
                "Berhasil memasuki ujian",
                200,
            );
        } catch (\Exception $e) {
            $statusCode =
                $e->getCode() >= 400 && $e->getCode() < 600
                    ? $e->getCode()
                    : 400;

            return $this->errorResponse($e->getMessage(), $statusCode);
        }
    }

    public function exit(Request $request, string $examId)
    {
        try {
            $type = $request->input("type") ?? null;
            $studentId = $request->user()->student->id;

            $attempt = $this->examAttemptService->exitExam(
                $studentId,
                $examId,
                $type,
            );

            return $this->successResponse(
                new ExamAttemptEnterResource($attempt),
                "Berhasil keluar dari ujian (status disimpan)",
                200,
            );
        } catch (\Exception $e) {
            $statusCode =
                $e->getCode() >= 400 && $e->getCode() < 600
                    ? $e->getCode()
                    : 400;
            return $this->errorResponse($e->getMessage(), $statusCode);
        } finally {
            if (isset($attempt)) {
                $this->activityLogService->log(
                    $request->user(),
                    "exit",
                    "Exam Attempt",
                );
            }
        }
    }

    public function submit(SubmitExamRequest $request, string $examId)
    {
        try {
            $validated = $request->validated();
            $user = $request->user();
            if (!$user || !$user->student()) {
                throw new \Exception("Student tidak ditemukan", 404);
            }

            $idRole = $user->student->id;
            $attempt = $this->examAttemptService->submitExam(
                $idRole,
                $examId,
                $validated["answers"] ?? [],
            );

            $this->activityLogService->log($user, "submit", "Exam Attempt");

            return $this->successResponse(
                new ExamAttemptEnterResource($attempt),
                "Ujian berhasil disubmit",
                200,
            );
        } catch (\Exception $e) {
            $statusCode =
                $e->getCode() >= 400 && $e->getCode() < 600
                    ? $e->getCode()
                    : 400;

            return $this->errorResponse($e->getMessage(), $statusCode);
        }
    }
    public function edit(SubmitExamRequest $request, string $examId)
    {
        try {
            $validated = $request->validated();
            $studentId = $request->input("student_id") ?? "";
            $user = $request->user();
            if (!$user || $user->role !== "teacher") {
                throw new \Exception("Akses ditolak", 403);
            }
            $idRole = $studentId;
            $attempt = $this->examAttemptService->submitExam(
                $idRole,
                $examId,
                $validated["answers"] ?? [],
            );

            $this->activityLogService->log($user, "edit", "Exam Attempt");

            return $this->successResponse(
                new ExamAttemptEnterResource($attempt),
                "Ujian berhasil disubmit",
                200,
            );
        } catch (\Exception $e) {
            $statusCode =
                $e->getCode() >= 400 && $e->getCode() < 600
                    ? $e->getCode()
                    : 400;

            return $this->errorResponse($e->getMessage(), $statusCode);
        }
    }
}
