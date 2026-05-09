<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponseFactory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

abstract class Controller
{
    use AuthorizesRequests;

    protected function success(
        mixed $data = [],
        string $message = 'Operation successful.',
        array $meta = [],
        int $status = 200,
    ): JsonResponse {
        return ApiResponseFactory::success($data, $message, $meta, $status);
    }

    protected function paginated(
        LengthAwarePaginator $paginator,
        mixed $data,
        string $message = 'Operation successful.',
        array $meta = [],
    ): JsonResponse {
        return ApiResponseFactory::paginated($paginator, $data, $message, $meta);
    }
}
