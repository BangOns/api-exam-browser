<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\ExamAnswerController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\ExamScheduleController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\ExamAttemptController;
use App\Http\Controllers\ExamTokenController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\SystemSettingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post("/register", [AuthController::class, "register"]);
Route::post("/login", [AuthController::class, "login"]);

Route::middleware(["auth:sanctum"])->group(function () {
    Route::post("/refresh", [AuthController::class, "refresh"])->middleware(
        "ability:issue_access_api",
    );

    Route::middleware(["ability:access_api"])->group(function () {
        Route::post("/logout", [AuthController::class, "logout"]);

        // ==========================================
        // 1. ROLE: ADMIN
        // ==========================================
        Route::middleware(["ability:role:admin"])->group(function () {
            Route::apiResource("report", ActivityLogController::class);
            Route::apiResource("class", ClassController::class);
            Route::apiResource("teacher", TeacherController::class);
            Route::apiResource("student", StudentController::class);
            Route::apiResource("subjects", SubjectController::class);

            // Admin melihat semua ujian
            Route::get("exam", [ExamController::class, "index"]);

            Route::post("exam-tokens/{exam}/generate", [
                ExamTokenController::class,
                "generate",
            ]);
            Route::post("exam-settings", [
                SystemSettingController::class,
                "update",
            ]);
        });

        // ==========================================
        // 2. ROLE: TEACHER
        // ==========================================
        Route::middleware(["ability:role:teacher"])->group(function () {
            Route::apiResource("question", QuestionController::class);
            Route::apiResource("lesson", LessonController::class);
            Route::apiResource("exam-answers", ExamAnswerController::class);

            Route::put("exam-attempts/{exam}/edit", [
                ExamAttemptController::class,
                "edit",
            ]);
            // Guru melihat ujian miliknya sendiri
            Route::get("exam-teacher", [
                ExamController::class,
                "indexByTeacherId",
            ]);
        });

        // ==========================================
        // 3. ROLE: STUDENT
        // ==========================================
        Route::middleware(["ability:role:student"])->group(function () {
            Route::post("exam-attempts/{exam}/enter", [
                ExamAttemptController::class,
                "enter",
            ]);
            Route::post("exam-attempts/{exam}/exit", [
                ExamAttemptController::class,
                "exit",
            ]);
            Route::post("exam-attempts/{exam}/submit", [
                ExamAttemptController::class,
                "submit",
            ]);

            // Siswa melihat ujian untuknya
            Route::get("exam-student", [
                ExamController::class,
                "indexByStudentId",
            ]);
        });

        // ==========================================
        // 4. SHARED: ADMIN & TEACHER
        // Mencegah saling timpa endpoint create, update, delete ujian
        // ==========================================
        Route::middleware(["ability:role:admin,role:teacher"])->group(
            function () {
                Route::post("exam", [ExamController::class, "store"]);
                Route::put("exam/{exam}", [ExamController::class, "update"]);
                Route::delete("exam/{exam}", [
                    ExamController::class,
                    "destroy",
                ]);
            },
        );

        // ==========================================
        // 5. SHARED: ADMIN, TEACHER, & STUDENT
        // Rute yang bisa diakses oleh semua role
        // ==========================================
        Route::middleware([
            "ability:role:teacher,role:admin,role:student",
        ])->group(function () {
            // Semua role bisa melihat detail ujian spesifik
            Route::get("exam/{exam}", [ExamController::class, "show"]);

            Route::apiResource("exam-schedules", ExamScheduleController::class);
            Route::get("exams/{exam}/monitor", [
                ExamController::class,
                "monitor",
            ]);
            Route::get("exam-attempts/{exam}", [
                ExamAttemptController::class,
                "index",
            ]);
        });

        // ==========================================
        // Cek Data User Aktif
        // ==========================================
        Route::get("/user", function (Request $request) {
            return $request->user();
        });
    });
});
