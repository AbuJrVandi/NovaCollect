<?php

declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpFoundation\Response;

class BusinessLogicException extends BaseApiException
{
    protected int $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY;
}
