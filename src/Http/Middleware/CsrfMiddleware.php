<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Solo\Inertia\Inertia;

/**
 * Cross-site request forgery protection that needs no session. The browser gets
 * a random token in the XSRF-TOKEN cookie; the Inertia client reads it and sends
 * it back in the X-XSRF-TOKEN header; a POST, PUT, PATCH or DELETE whose header
 * does not match the cookie is refused. Another site can make the browser send
 * the cookie, but can neither read it nor set the header.
 *
 * A refused form goes back to its page with a message, as when a session
 * expires, and the response carries a token for the next try.
 */
final readonly class CsrfMiddleware implements MiddlewareInterface
{
    private const string COOKIE = 'XSRF-TOKEN';
    private const string HEADER = 'X-XSRF-TOKEN';

    public function __construct(
        private Inertia $inertia,
        private ResponseFactoryInterface $responses,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $token = $request->getCookieParams()[self::COOKIE] ?? null;
        $token = is_string($token) && preg_match('/^[0-9a-f]{64}$/', $token) === 1 ? $token : null;

        $safe = in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'], true);
        $response = $safe || ($token !== null && hash_equals($token, $request->getHeaderLine(self::HEADER)))
            ? $handler->handle($request)
            : $this->expired($request);

        return $token === null ? self::withNewToken($request, $response) : $response;
    }

    private function expired(ServerRequestInterface $request): ResponseInterface
    {
        if (!Inertia::isInertia($request)) {
            return $this->responses->createResponse(419);
        }

        return $this->inertia->back(
            $request,
            $this->responses->createResponse(),
            flash: ['error' => 'The page expired, please try again.'],
        );
    }

    private static function withNewToken(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        // Readable by the client, so not HttpOnly; Lax keeps it out of requests other sites start
        $cookie = self::COOKIE . '=' . bin2hex(random_bytes(32)) . '; Path=/; SameSite=Lax';
        if ($request->getUri()->getScheme() === 'https') {
            $cookie .= '; Secure';
        }

        return $response->withAddedHeader('Set-Cookie', $cookie);
    }
}
