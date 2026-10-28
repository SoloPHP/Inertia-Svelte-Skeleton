<?php

declare(strict_types=1);

namespace App\Http\Exceptions;

use RuntimeException;

/**
 * An error the client caused, answered with the Error page and its status. Throw
 * it from anywhere below ErrorMiddleware instead of building a response by hand.
 */
class HttpException extends RuntimeException
{
    public function __construct(public readonly int $status)
    {
        parent::__construct("HTTP $status", $status);
    }
}
