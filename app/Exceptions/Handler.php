<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Http\Responses\ApiResponseFactory;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class Handler extends ExceptionHandler
{
    protected $dontReport = [];

    public function render($request, Throwable $e): Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            if ($e instanceof BaseApiException) {
                return ApiResponseFactory::error(
                    message: $e->getMessage(),
                    errors: $e->getErrors(),
                    status: $e->getStatusCode(),
                );
            }
        }

        return parent::render($request, $e);
    }
}
