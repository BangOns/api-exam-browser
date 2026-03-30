<?php

namespace App\Exceptions;

use Exception;

class InvalidLoginException extends Exception
{
    public function render($request)
    {
        return response()->json([
            'status' => false,
            'code' => 401,
            'message' => 'Username atau password salah.'
        ], 401);
    }
}
