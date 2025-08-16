<?php

namespace App\Traits\Responses;

use Illuminate\Http\JsonResponse;

trait HandlesApiErrors
{
    /**
     * Return a standardized JSON error response.
     *
     * @param  string  $message
     * @param  int     $status  HTTP status code (default 500)
     * @param  array   $extra   Additional data to include in the response
     * @return \Illuminate\Http\JsonResponse
     */
    public function errorResponse(string $message = 'Error.', int $status = 500, array $extra = []): JsonResponse
    {
        $response = [
            'status'  => false,
            'message' => $message,
        ];

        if (!empty($extra) && is_array($extra)) {
            $response = array_merge($response, $extra);
        }

        // combine any extra data (data or errors)
        // like : return $helper->errorResponse('Validation failed', 422, [
            //     'errors' => $e->errors(), // Returns array of field-specific errors
            // ]);
 
        return response()->json($response, $status);
    }
}
