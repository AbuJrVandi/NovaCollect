<?php

declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

abstract class BaseApiException extends \RuntimeException
{
    protected int $statusCode = Response::HTTP_INTERNAL_SERVER_ERROR;

    protected array $errors = [];

    public function __construct(string $message = '', array $errors = [], ?int $statusCode = null, ?\Throwable $previous = null)
    {
        parent::__construct($message, $statusCode ?? 0, $previous);
        $this->errors = $errors;
        if ($statusCode !== null) {
            $this->statusCode = $statusCode;
        }
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
