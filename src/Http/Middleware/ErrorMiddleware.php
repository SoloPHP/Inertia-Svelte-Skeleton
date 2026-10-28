<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\Exceptions\HttpException;
use App\Http\ValidationMessages;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Solo\Application\Exception\RoutingException;
use Solo\Inertia\Inertia;
use Solo\Input\Exceptions\ValidationException;
use Throwable;

/**
 * The one place where exceptions become responses. Invalid input goes back to the
 * form it came from, whose errors prop shows the messages. A client error is the
 * Error page with its status; anything else is a logged 500, shown as the Error
 * page, or with its details in debug mode.
 *
 * InertiaMiddleware wraps it, so its redirects follow the protocol: the one after
 * an invalid PATCH is a 303.
 */
final readonly class ErrorMiddleware implements MiddlewareInterface
{
    public function __construct(
        private Inertia $inertia,
        private ResponseFactoryInterface $responses,
        private LoggerInterface $logger,
        private bool $debug = false,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (ValidationException $e) {
            return $this->invalid($request, $e);
        } catch (HttpException $e) {
            return $this->errorPage($request, $e->status);
        } catch (RoutingException $e) {
            // 404, 405 with its Allow header, 400 for a malformed route parameter
            return $e->applyTo($this->errorPage($request, $e->getStatusCode()));
        } catch (Throwable $e) {
            return $this->serverError($e, $request);
        }
    }

    /** Back to the form, whose errors prop shows the messages; a bad query string has no form */
    private function invalid(ServerRequestInterface $request, ValidationException $e): ResponseInterface
    {
        if (in_array($request->getMethod(), ['GET', 'HEAD'], true)) {
            return $this->errorPage($request, 400);
        }

        try {
            return $this->inertia->back(
                $request,
                $this->responses->createResponse(),
                errors: ValidationMessages::for($e->getErrors()),
            );
        } catch (Throwable $failed) {
            // Such as more errors than the flash cookie holds
            return $this->serverError($failed, $request);
        }
    }

    private function errorPage(ServerRequestInterface $request, int $status): ResponseInterface
    {
        try {
            return $this->inertia->render(
                $request,
                $this->responses->createResponse($status),
                'Error',
                ['status' => $status],
            );
        } catch (Throwable $e) {
            // The page itself failed, such as a shared prop or a missing build: plain text, then
            $this->log($e, $request);

            return $this->plain($status, "Error $status");
        }
    }

    private function serverError(Throwable $e, ServerRequestInterface $request): ResponseInterface
    {
        $this->log($e, $request);

        if (!$this->debug) {
            return $this->errorPage($request, 500);
        }

        // Not a page: the Inertia client shows it in a modal, the browser as it is
        return $this->plain(500, sprintf(
            "%s: %s\n\nat %s:%d\n\n%s",
            $e::class,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString(),
        ));
    }

    private function log(Throwable $e, ServerRequestInterface $request): void
    {
        $this->logger->error('{method} {path}: {exception}', [
            'method' => $request->getMethod(),
            'path' => $request->getUri()->getPath(),
            'exception' => $e,
        ]);
    }

    private function plain(int $status, string $text): ResponseInterface
    {
        $response = $this->responses->createResponse($status)->withHeader('Content-Type', 'text/plain; charset=utf-8');
        $response->getBody()->write($text);

        return $response;
    }
}
