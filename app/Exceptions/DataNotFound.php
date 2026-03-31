<?php

namespace App\Exceptions;

use App\Traits\ApiResponse;
use Exception;

class DataNotFound extends Exception
{
    use ApiResponse;
    public function render($request)
    {
        return $this->errorResponse(
            'Data tidak ditemukan.',
            404,
        );
    }
}
