<?php declare(strict_types=1);

namespace App\Middlewares;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Solo\Configs;

class InertiaCommonPropsMiddleware implements MiddlewareInterface
{

    public function __construct(private ContainerInterface $container)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $settings = $this->container->get(Configs::class);

        $request = $request->withAttribute('inertiaCommonProps', [
            'environment' => $settings->get('environment')
        ]);

        return $handler->handle($request);
    }
}