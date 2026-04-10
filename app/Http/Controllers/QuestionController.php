<?php

namespace App\Http\Controllers;

use App\Http\Requests\Question\QuestionRequest;
use App\Http\Resources\Question\QuestionResource;
use App\Services\ActivityLogService;
use App\Services\QuestionService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    use ApiResponse;
    /**
     * Display a listing of the resource.
     */
    public function __construct(
        private QuestionService $questionService,
        private ActivityLogService $activityLogService
    ) {}
    public function index(Request $request)
    {
        $paginator = $this->questionService->getAllQuestions(5, $request->query('search', ''));
        return $this->successResponse(
            QuestionResource::collection($paginator),
            'Question retrieved successfully',
            200,
            [
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ]
            ]
        );
    }
    public function store(QuestionRequest $request)
    {
        $question = $this->questionService->createQuestion($request->validated());

        $this->activityLogService->log($request->user(), "create", 'Question');

        return $this->successResponse(
            new QuestionResource($question),
            'Question created successfully',
            201,
        );
    }
    public function show($id)
    {
        $question = $this->questionService->getQuestionById($id);
        return $this->successResponse(
            new QuestionResource($question),
            'Question retrieved successfully',
            200,
        );
    }
    public function update(QuestionRequest $request, $id)
    {
        $question = $this->questionService->updateQuestion($request->validated(), $id);

        $this->activityLogService->log($request->user(), "update", 'Question');

        return $this->successResponse(
            new QuestionResource($question),
            'Question updated successfully',
            200,
        );
    }
    public function destroy(Request $request, string $id)
    {
        $question = $this->questionService->getQuestionById($id);
        $this->questionService->deleteQuestion($id);

        $this->activityLogService->log($request->user(), "delete", 'Question');

        return $this->successResponse(
            $question ? new QuestionResource($question) : null,
            'Question deleted successfully',
            200,
        );
    }
}
