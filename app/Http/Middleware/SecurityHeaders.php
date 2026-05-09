<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Prevent clickjacking
        $response->header('X-Frame-Options', 'DENY');

        // Prevent MIME type sniffing
        $response->header('X-Content-Type-Options', 'nosniff');

        // Enable XSS protection
        $response->header('X-XSS-Protection', '1; mode=block');

        // Content Security Policy
        $response->header('Content-Security-Policy', "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self'");

        // Referrer Policy
        $response->header('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Enable HSTS
        $response->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');

        // Prevent unwanted feature usage
        $response->header('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        return $response;
    }
}
