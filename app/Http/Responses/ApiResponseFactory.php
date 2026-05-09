<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiResponseFactory
{
    public static function success(
        mixed $data = [],
        string $message = 'Operation successful.',
        array $meta = [],
        int $status = Response::HTTP_OK,
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'meta' => $meta,
        ], $status);
    }

    public static function paginated(
        LengthAwarePaginator $paginator,
        mixed $data,
        string $message = 'Operation successful.',
        array $meta = [],
    ): JsonResponse {
        return self::success($data, $message, array_merge($meta, [
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'from' => $paginator->firstItem(),
                'last_page' => $paginator->lastPage(),
                'path' => $paginator->path(),
                'per_page' => $paginator->perPage(),
                'to' => $paginator->lastItem(),
                'total' => $paginator->total(),
            ],
        ]));
    }

    public static function error(
        string $message = 'Request failed.',
        array $errors = [],
        int $status = Response::HTTP_BAD_REQUEST,
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors === [] ? new \stdClass() : $errors,
        ], $status);
    }
}
