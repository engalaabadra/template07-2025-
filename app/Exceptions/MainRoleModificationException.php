<?php

namespace App\Exceptions;

use Exception;

class MainRoleModificationException extends Exception
{
    public function render($request)
    {
        return response()->json([
            'status'  => false,                                 // Always false for failure
            'message' => $this->getMessage(), // Default error message
            'data' => null,                                    // Optional data
        ], 403);

        return response()->json([
            'message' => $this->getMessage(),
            'error' => true,
        ], 403);
    }
}
