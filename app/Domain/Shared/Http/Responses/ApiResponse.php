<?php

namespace App\Domain\Shared\Http\Responses;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class ApiResponse
{
    /**
     * Generate standard success JSON response.
     */
    public static function success(
        mixed $data = null,
        string $message = 'Operation successful.',
        int $status = 200,
        array $extraMeta = []
    ): JsonResponse {
        $meta = array_merge([
            'correlation_id' => request()->header('X-Correlation-ID', (string) Str::uuid()),
            'timestamp' => now()->toIso8601String(),
            'version' => 'v1',
        ], $extraMeta);

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => $meta,
        ], $status);
    }

    /**
     * Generate standard paginated JSON response.
     */
    public static function paginated(
        LengthAwarePaginator $paginator,
        string $message = 'Resources retrieved successfully.',
        int $status = 200,
        array $extraMeta = []
    ): JsonResponse {
        $meta = array_merge([
            'pagination' => [
                'total' => $paginator->total(),
                'count' => $paginator->count(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'total_pages' => $paginator->lastPage(),
                'has_more' => $paginator->hasMorePages(),
            ],
            'correlation_id' => request()->header('X-Correlation-ID', (string) Str::uuid()),
            'timestamp' => now()->toIso8601String(),
            'version' => 'v1',
        ], $extraMeta);

        $links = [
            'first' => $paginator->url(1),
            'last' => $paginator->url($paginator->lastPage()),
            'prev' => $paginator->previousPageUrl(),
            'next' => $paginator->nextPageUrl(),
        ];

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $paginator->items(),
            'meta' => $meta,
            'links' => $links,
        ], $status);
    }

    /**
     * Generate standard error JSON response.
     */
    public static function error(
        string $message = 'An error occurred.',
        string $errorCode = 'INTERNAL_ERROR',
        array $errors = [],
        int $status = 400
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'code' => $errorCode,
            'error_code' => $errorCode,
            'errors' => (object) $errors,
            'meta' => [
                'correlation_id' => request()->header('X-Correlation-ID', (string) Str::uuid()),
                'timestamp' => now()->toIso8601String(),
            ],
        ], $status);
    }
}
