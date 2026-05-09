<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ApiRateLimit
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 120 requests per minute per IP
        $limit = RateLimiter::attempt(
            'api:' . $request->ip(),
            perMinute: 120
        );

        if (!$limit) {
            return response()->json([
                'status' => false,
                'message' => 'Too many requests. Please try again in a moment.'
            ], 429);
        }

        return $next($request);
    }
}
