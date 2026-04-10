<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\User\UserResource;
use App\Services\ActivityLogService;
use App\Services\AuthService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        private AuthService $authService,
        private ActivityLogService $activityLogService
    ) {}

    /**
     * Handle user registration.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = $this->authService->register($request->validated(), $request->ip(), $request->userAgent());

        $this->activityLogService->log($user['user'], "register", 'Auth');

        return $this->successResponse(
            new UserResource($user['user']),
            'User registered successfully',
            201
        );
    }

    /**
     * Handle user login.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated(), $request->ip(), $request->userAgent());

        $this->activityLogService->log($result['user'], "login", 'Auth');

        return $this->successResponse([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
            'refresh_token' => $result['refresh_token'],
        ], 'Login successful');
    }
    /**
     * Handle user refresh token.
     */
    public function refresh(Request $request): JsonResponse
    {
        $result = $this->authService->refreshToken($request->user(), $request->ip());

        return $this->successResponse([
            'token'      => $result['token'],
            'expires_in' => $result['expires_in'],
            'token_type' => 'Bearer',
        ], 'Token refreshed successfully');
    }

    /**
     * Handle user logout.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $this->authService->logout($user, $request->ip());

        $this->activityLogService->log($user, "logout", 'Auth');

        return $this->successResponse(null, 'Logout successful');
    }
}
