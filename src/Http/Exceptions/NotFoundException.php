<?php

declare(strict_types=1);

namespace App\Http\Exceptions;

final class NotFoundException extends HttpException
{
    public function __construct()
    {
        parent::__construct(404);
    }
}
