<?php

declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class ValidationException extends BaseApiException
{
    public function __construct(string $message = 'Validation failed.', array $errors = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, $errors, Response::HTTP_UNPROCESSABLE_ENTITY, $previous);
    }
}
