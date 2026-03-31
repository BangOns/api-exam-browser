<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\TeacherController;
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
            });
            Route::get('/user', function (Request $request) {
                return $request->user();
            });
        });
});
