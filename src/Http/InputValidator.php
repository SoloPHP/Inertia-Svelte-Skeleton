<?php

declare(strict_types=1);

namespace App\Http;

use LogicException;
use Psr\Http\Message\ServerRequestInterface;
use Solo\Application\RequestAttribute;
use Solo\Inertia\Contracts\RequestValidatorInterface;
use Solo\Input\Exceptions\ValidationException;
use Solo\Input\InputFactory;

/**
 * Validates a form while the user fills it in (Precognition), with the input class
 * its route names, as the handler would validate it on submit:
 *
 *     $r->post('', [TaskController::class, 'store'])->attribute('input', TaskStoreInput::class);
 *
 * PrecognitionMiddleware asks it instead of running the handler, so a request that
 * only validates never saves anything. A route that names no input class fails.
 */
final readonly class InputValidator implements RequestValidatorInterface
{
    public function __construct(private InputFactory $inputs)
    {
    }

    public function errors(ServerRequestInterface $request): array
    {
        $attributes = $request->getAttribute(RequestAttribute::ROUTE_ATTRIBUTES);
        $class = is_array($attributes) ? $attributes['input'] ?? null : null;
        if (!is_string($class) || !class_exists($class)) {
            throw new LogicException(sprintf(
                "Route %s names no input class to validate: add ->attribute('input', SomeInput::class)",
                $request->getMethod() . ' ' . $request->getUri()->getPath(),
            ));
        }

        $params = $request->getAttribute(RequestAttribute::PARAMS);
        /** @var array<string, string> $route */
        $route = is_array($params) ? $params : [];

        try {
            $this->inputs->fromBody($class, $request, $route);
        } catch (ValidationException $e) {
            return ValidationMessages::for($e->getErrors());
        }

        return [];
    }
}
