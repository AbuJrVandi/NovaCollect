<?php

declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class NotFoundException extends BaseApiException
{
    public function __construct(string $message = 'Resource not found.', array $errors = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, $errors, Response::HTTP_NOT_FOUND, $previous);
    }
}
