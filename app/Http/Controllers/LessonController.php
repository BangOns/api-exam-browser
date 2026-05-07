<?php

namespace App\Http\Controllers;

use App\Http\Resources\Lesson\LessonResource;
use App\Services\LessonService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    use ApiResponse;

    public function __construct(
        private LessonService $lessonService,
    ) {}
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $paginator = $this->lessonService->getAllLesson($request->query('search', ''));
        return $this->successResponse(
            LessonResource::collection($paginator),
            'Lesson retrieved successfully',
            200,
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
