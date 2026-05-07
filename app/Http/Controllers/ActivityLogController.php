<?php

namespace App\Http\Controllers;

use App\Http\Resources\ActivityLog\ActivityLogResource;
use App\Services\ActivityLogService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    use ApiResponse;
    public function __construct(private ActivityLogService $activityLogService) {}
    public function index(Request $request)
    {
        $activityLogs = $this->activityLogService->getAllActivityLogs($request->per_page ?? 5, $request->search);

        return $this->successResponse(
            ActivityLogResource::collection($activityLogs),
            'Activity logs retrieved successfully',
            200,
            [
                'pagination' => [
                    'current_page' => $activityLogs->currentPage(),
                    'last_page' => $activityLogs->lastPage(),
                    'per_page' => $activityLogs->perPage(),
                    'total' => $activityLogs->total(),
                ]
            ]
        );
    }
}
