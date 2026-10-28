<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Solo\Dotenv\Dotenv;
use Solo\Inertia\Inertia;
use Solo\Router\UrlGenerator;

/**
 * The props every page gets, the error page included. Shared props travel with
 * the request, never in a service, so a long-running worker starts each request
 * with none.
 */
final readonly class SharedPropsMiddleware implements MiddlewareInterface
{
    public function __construct(private UrlGenerator $urls)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle(Inertia::share($request, [
            'app' => ['name' => Dotenv::string('APP_NAME', 'Solo')],
            // The links of the layout, by route name. A once prop: the client keeps it from the first
            // page on, and the server skips the Closure while the client has it
            'nav' => Inertia::once(fn() => [
                'home' => $this->urls->generate('home'),
                'links' => [
                    ['label' => 'Home', 'url' => $this->urls->generate('home')],
                    ['label' => 'Tasks', 'url' => $this->urls->generate('tasks.index')],
                ],
            ]),
        ]));
    }
}
