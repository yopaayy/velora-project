<?php

namespace App\Shared\Resources;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success($data = null, string $message = 'Success', int $statusCode = 200, array $meta = []): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => array_merge([
                'timestamp' => now()->toIso8601String(),
                'request_id' => request()?->header('X-Request-Id', uniqid('req_')),
            ], $meta),
        ], $statusCode);
    }

    public static function created($data = null, string $message = 'Created successfully'): JsonResponse
    {
        return static::success($data, $message, 201);
    }

    public static function error(string $message = 'Error', int $statusCode = 400, array $errors = [], ?string $errorCode = null): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors ?: (object) [],
            'meta' => [
                'timestamp' => now()->toIso8601String(),
                'error_code' => $errorCode,
                'request_id' => request()?->header('X-Request-Id', uniqid('req_')),
            ],
        ], $statusCode);
    }

    public static function paginated($paginator, string $message = 'Data retrieved'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'timestamp' => now()->toIso8601String(),
            ],
        ]);
    }

    public static function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }
}
