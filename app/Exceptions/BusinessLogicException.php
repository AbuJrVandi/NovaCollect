<?php

declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class BusinessLogicException extends BaseApiException
{
    public function __construct(string $message = 'Operation could not be completed.', array $errors = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, $errors, Response::HTTP_UNPROCESSABLE_ENTITY, $previous);
    }
}
