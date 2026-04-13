<?php

use App\Http\Middleware\Authenticate;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth'      => Authenticate::class,
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
            'throttle' => ThrottleRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {

        $exceptions->render(function (AuthenticationException $e, Request $request) {
            return response()->json([
                'status'  => 'error',
                'code'    => 401,
                'message' => 'Access token tidak valid atau sudah kadaluarsa.'
            ], 401);
        });

        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) {
            return $request->is('api/*') || $request->expectsJson();
        });

        $exceptions->render(function (ModelNotFoundException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'code' => 404,
                    'message' => 'Data tidak ditemukan.'
                ], 404);
            }
        });
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'code' => 404,
                    'message' => $e->getMessage() ?? 'Endpoint not found.'
                ], 404);
            }
        });

        $exceptions->render(function (AccessDeniedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'code' => 403,
                    'message' => 'Anda tidak memiliki izin.'
                ], 403);
            }
        });
        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'code' => 405,
                    'message' => 'Method not allowed.'
                ], 405);
            }
        });
        $exceptions->render(function (Throwable $e, Request $request) {

            if ($request->is('api/*')) {
                $message = match (true) {
                    $e instanceof QueryException => match (true) {
                        str_contains($e->getMessage(), 'invalid input syntax for type uuid') => 'Format UUID tidak valid.',
                        str_contains($e->getMessage(), 'duplicate key')                      =>  $e->getMessage() ?? 'Data sudah ada.',
                        str_contains($e->getMessage(), 'foreign key')                        => 'Data tidak bisa dihapus karena masih digunakan.',
                        default                                                              => $e->getMessage() ?? 'Terjadi kesalahan pada database.',
                    },
                    default => $e->getMessage(),
                };

                return response()->json([
                    'status' => false,
                    'message' => $message
                ], 500);
            }
        });
    })->create();
