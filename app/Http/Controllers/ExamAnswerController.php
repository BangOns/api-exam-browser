<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExamAnswer\ExamAnswerRequest;
use App\Http\Resources\ExamAnswer\ExamAnswerResource;
use App\Services\ExamAnswerService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ExamAnswerController extends Controller
{
    use ApiResponse;
    public function __construct(private ExamAnswerService $examAnswerService) {}
    public function index(Request $request)
    {
        $paginator = $this->examAnswerService->getAllExamAnswers($request->query('per_page', 5), $request->query('search', ''));
        return $this->successResponse(
            ExamAnswerResource::collection($paginator),
            'Data berhasil diambil',
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
    // public function store(ExamAnswerRequest $request)
    // {
    //     $examAnswer = $this->examAnswerService->createExamAnswer($request->validated());
    //     return $this->successResponse(new ExamAnswerResource($examAnswer), 'Data berhasil ditambahkan', 201);
    // }
    // public function show(string $id)
    // {
    //     $examAnswer = $this->examAnswerService->getExamAnswerById($id);
    //     return $this->successResponse(new ExamAnswerResource($examAnswer), 'Data berhasil diambil', 200);
    // }
    // public function update(ExamAnswerRequest $request, string $id)
    // {
    //     $examAnswer = $this->examAnswerService->updateExamAnswer($request->validated(), $id);
    //     return $this->successResponse(new ExamAnswerResource($examAnswer), 'Data berhasil diupdate', 200);
    // }
    // public function destroy(string $id)
    // {
    //     $this->examAnswerService->deleteExamAnswer($id);
    //     return $this->successResponse(null, 'Data berhasil dihapus', 200);
    // }
}
