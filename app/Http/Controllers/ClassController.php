<?php

namespace App\Http\Controllers;

use App\Http\Requests\Class\ClassRequest;
use App\Http\Resources\Class\ClassResource;
use App\Services\ClassService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ClassController extends Controller
{
    use ApiResponse;
    /**
     * Display a listing of the resource.
     */
    public function __construct(private ClassService $classService) {}
    public function index(Request $request)
    {
        $paginator = $this->classService->getAllClasses(5, $request->query('search', ''));
        return $this->successResponse(
            ClassResource::collection($paginator),
            'Kelas retrieved successfully',
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

    public function show(string $id)
    {
        $class = $this->classService->getClassById($id);



        return $this->successResponse(
            new ClassResource($class),
            'Kelas retrieved successfully',
            200,
        );
    }

    /**
     * POST /api/classes
     * Buat kelas baru. Hanya admin.
     */
    public function store(ClassRequest $request)
    {
        $class = $this->classService->createClass($request->validated());

        return $this->successResponse(
            new ClassResource($class),
            'Kelas created successfully',
            201,
        );
    }

    /**
     * PUT/PATCH /api/classes/{id}
     * Update kelas. Hanya admin.
     */
    public function update(ClassRequest $request, string $id)
    {
        $this->classService->updateClass($id, $request->validated());



        return $this->successResponse(
            null,
            'Kelas updated successfully',
            200,
        );
    }

    /**
     * DELETE /api/classes/{id}
     * Hapus kelas. Hanya admin.
     */
    public function destroy(string $id)
    {
        $this->classService->deleteClass($id);


        return $this->successResponse(
            null,
            'Kelas deleted successfully',
            200,
        );
    }
}
