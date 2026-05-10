<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Http\Responses\ApiResponseFactory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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

            if ($e instanceof ValidationException) {
                return ApiResponseFactory::error(
                    message: 'Validation failed.',
                    errors: $e->errors(),
                    status: Response::HTTP_UNPROCESSABLE_ENTITY,
                );
            }

            if ($e instanceof AuthenticationException) {
                return ApiResponseFactory::error(
                    message: 'Unauthenticated.',
                    status: Response::HTTP_UNAUTHORIZED,
                );
            }

            if ($e instanceof AuthorizationException) {
                return ApiResponseFactory::error(
                    message: 'This action is unauthorized.',
                    status: Response::HTTP_FORBIDDEN,
                );
            }

            if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
                return ApiResponseFactory::error(
                    message: 'Resource not found.',
                    status: Response::HTTP_NOT_FOUND,
                );
            }

            if ($e instanceof ThrottleRequestsException) {
                return ApiResponseFactory::error(
                    message: 'Too many requests. Please slow down.',
                    status: Response::HTTP_TOO_MANY_REQUESTS,
                );
            }

            if ($e instanceof HttpException) {
                return ApiResponseFactory::error(
                    message: $e->getMessage() ?: 'Request failed.',
                    status: $e->getStatusCode(),
                );
            }

            return ApiResponseFactory::error(
                message: 'An unexpected server error occurred.',
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        }

        return parent::render($request, $e);
    }
}
