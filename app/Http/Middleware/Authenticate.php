<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class Authenticate extends Middleware
{
    protected function redirectTo(Request $request): ?string
    {
        // Jika request API atau expects JSON, return null (tidak redirect)
        // Ini akan menyebabkan AuthenticationException dilempar tanpa redirect
        if ($request->is('api/*') || $request->expectsJson() || !Route::has('login')) {
            return null;
        }

        return route('login');
    }
}
