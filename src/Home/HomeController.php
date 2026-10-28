<?php

declare(strict_types=1);

namespace App\Home;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Solo\Inertia\Inertia;
use Solo\Router\UrlGenerator;

/**
 * An invokable handler: a route may name a class with __invoke() instead of a
 * [class, method] pair. It renders resources/js/Pages/Home.svelte.
 */
final readonly class HomeController
{
    public function __construct(
        private Inertia $inertia,
        private UrlGenerator $urls,
    ) {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->inertia->render($request, $response, 'Home', [
            'tasksUrl' => $this->urls->generate('tasks.index'),
            'php' => PHP_VERSION,
        ]);
    }
}
