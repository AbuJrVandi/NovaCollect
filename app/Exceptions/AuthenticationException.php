<?php

declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class AuthenticationException extends BaseApiException
{
    public function __construct(string $message = 'Unauthenticated.', array $errors = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, $errors, Response::HTTP_UNAUTHORIZED, $previous);
    }
}
