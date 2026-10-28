<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Exceptions\HttpException;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Puts a decoded JSON body into the request's parsed body, where input DTOs read
 * it. A body that is not a JSON object or array is a 400.
 */
final readonly class JsonBodyMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!str_contains(strtolower($request->getHeaderLine('Content-Type')), 'json')) {
            return $handler->handle($request);
        }

        $body = (string) $request->getBody();
        if (trim($body) === '') {
            return $handler->handle($request);
        }

        try {
            $data = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw new HttpException(400);
        }

        if (!is_array($data)) {
            throw new HttpException(400);
        }

        return $handler->handle($request->withParsedBody($data));
    }
}
