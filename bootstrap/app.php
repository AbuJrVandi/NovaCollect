<?php

declare(strict_types=1);

use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\SetSecurityHeaders;
use App\Http\Responses\ApiResponseFactory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->api(prepend: [
            ForceJsonResponse::class,
            SetSecurityHeaders::class,
        ]);

        $middleware->alias([
            'force.json' => ForceJsonResponse::class,
            'secure.headers' => SetSecurityHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponseFactory::error(
                message: 'Validation failed.',
                errors: $exception->errors(),
                status: Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponseFactory::error(
                message: 'Unauthenticated.',
                status: Response::HTTP_UNAUTHORIZED,
            );
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponseFactory::error(
                message: $exception->getMessage() !== '' ? $exception->getMessage() : 'You are not authorized to perform this action.',
                status: Response::HTTP_FORBIDDEN,
            );
        });

        $exceptions->render(function (ModelNotFoundException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponseFactory::error(
                message: 'Resource not found.',
                status: Response::HTTP_NOT_FOUND,
            );
        });

        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponseFactory::error(
                message: 'Endpoint not found.',
                status: Response::HTTP_NOT_FOUND,
            );
        });

        $exceptions->render(function (ThrottleRequestsException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponseFactory::error(
                message: 'Too many requests.',
                status: Response::HTTP_TOO_MANY_REQUESTS,
            );
        });

        $exceptions->render(function (HttpExceptionInterface $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return ApiResponseFactory::error(
                message: $exception->getMessage() !== '' ? $exception->getMessage() : Response::$statusTexts[$exception->getStatusCode()],
                status: $exception->getStatusCode(),
            );
        });

        $exceptions->render(function (\Throwable $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            report($exception);

            return ApiResponseFactory::error(
                message: 'An unexpected server error occurred.',
                status: Response::HTTP_INTERNAL_SERVER_ERROR,
            );
        });
    })
    ->create();
