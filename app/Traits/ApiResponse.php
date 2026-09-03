<?php

namespace App\Traits;

trait ApiResponse
{
    /**
     * Success Response Structure
     */
    protected function successResponse($data = null, string $message = 'Success', int $statusCode = 200)
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data'    => $data,
        ], $statusCode);
    }

    /**
     * Error Response Structure
     */
    protected function errorResponse(string $message = 'Error', int $statusCode = 400, $errors = null)
    {
        return response()->json([
            'status' => false,
            'message' => $message,
            'errors'  => $errors,
        ], $statusCode);
    }
}
