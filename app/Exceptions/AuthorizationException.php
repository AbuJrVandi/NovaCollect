<?php

declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class AuthorizationException extends BaseApiException
{
    public function __construct(string $message = 'Forbidden.', array $errors = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, $errors, Response::HTTP_FORBIDDEN, $previous);
    }
}
