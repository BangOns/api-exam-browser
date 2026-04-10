<?php

namespace App\Http\Controllers;

use App\Http\Requests\SystemSetting\SystemSettingRequest;
use App\Http\Resources\SystemSetting\SystemSettingResource;
use App\Services\SystemSettingService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class SystemSettingController extends Controller
{
    use ApiResponse;
    public function __construct(private SystemSettingService $systemSettingService) {}
    public function update(SystemSettingRequest $request)
    {
        $systemSetting = $this->systemSettingService->set('exam_security', $request->validated());
        return $this->successResponse(new SystemSettingResource($systemSetting), 'Data berhasil diupdate', 200);
    }
}
