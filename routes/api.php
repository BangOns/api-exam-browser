<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\ExamController;
use App\Http\Controllers\ExamScheduleController;
use App\Http\Controllers\QuestionController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\ExamAttemptController;
use App\Http\Controllers\ExamTokenController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum'])->group(function () {

    Route::post('/refresh', [AuthController::class, 'refresh'])
        ->middleware('ability:issue_access_api');

    Route::middleware(['ability:access_api'])
        ->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::middleware(['ability:role:admin'])->group(function () {
                // class
                Route::apiResource('class', ClassController::class);
                Route::apiResource('teacher', TeacherController::class);
                Route::apiResource('student', StudentController::class);
                Route::post('exam-tokens/{exam}/generate', [ExamTokenController::class, 'generate']);
            });
            Route::middleware(['ability:role:teacher'])->group(function () {
                Route::apiResource('question', QuestionController::class);
                Route::apiResource('exam', ExamController::class);
            });
            Route::middleware(['ability:role:student'])->group(function () {
                Route::post('exam-attempts/{exam}/enter', [ExamAttemptController::class, 'enter']);
                Route::post('exam-attempts/{exam}/exit', [ExamAttemptController::class, 'exit']);
                Route::post('exam-attempts/{exam}/submit', [ExamAttemptController::class, 'submit']);
            });
            Route::middleware(['ability:role:teacher,role:admin'])->group(function () {
                Route::apiResource('exam-schedules', ExamScheduleController::class);
            });
            Route::get('/user', function (Request $request) {
                return $request->user();
            });
        });
});
