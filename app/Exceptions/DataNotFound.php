<?php

namespace App\Exceptions;

use Exception;

class DataNotFound extends Exception
{
    public function render($request)
    {
        return response()->json([
            'status' => false,
            'code' => 404,
            'message' => $this->message ?? 'Data tidak ditemukan.'
        ], 404);
    }
}
