<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Middleware\ErrorMiddleware;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Solo\Contracts\Container\WritableContainerInterface;
use Solo\Contracts\Http\EmitterInterface;
use Solo\Dotenv\Dotenv;
use Solo\HttpEmitter\Emitter;
use Solo\Inertia\Inertia;

/**
 * The HTTP layer: PSR-17 factories, the incoming request, the emitter and the
 * error middleware. Nyholm is the PSR-7 implementation; the rest of the
 * application sees only the PSR interfaces, so this is the one file to change
 * to use another one.
 *
 * A factory receives the container. Typed as WritableContainerInterface, its
 * get(Foo::class) is a Foo to static analysis, with no cast.
 */
final class HttpServiceProvider
{
    public function register(WritableContainerInterface $container): void
    {
        $container->set(Psr17Factory::class, fn() => new Psr17Factory());
        $container->set(
            ResponseFactoryInterface::class,
            fn(WritableContainerInterface $c) => $c->get(Psr17Factory::class),
        );
        $container->set(
            StreamFactoryInterface::class,
            fn(WritableContainerInterface $c) => $c->get(Psr17Factory::class),
        );

        // Application::run() handles this request; tests pass their own to handle()
        $container->set(ServerRequestInterface::class, function (WritableContainerInterface $c) {
            $factory = $c->get(Psr17Factory::class);

            return (new ServerRequestCreator($factory, $factory, $factory, $factory))->fromGlobals();
        });

        $container->set(EmitterInterface::class, fn() => new Emitter());

        $container->set(ErrorMiddleware::class, fn(WritableContainerInterface $c) => new ErrorMiddleware(
            $c->get(Inertia::class),
            $c->get(ResponseFactoryInterface::class),
            $c->get(LoggerInterface::class),
            debug: Dotenv::bool('APP_DEBUG'),
        ));
    }
}
