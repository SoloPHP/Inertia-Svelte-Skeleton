<?php

declare(strict_types=1);

namespace App\Providers;

use Solo\Contracts\Container\WritableContainerInterface;
use Solo\Contracts\Validator\ValidatorInterface;
use Solo\Input\InputFactory;
use Solo\Validator\Validator;

/**
 * Request DTOs: solophp/input builds them, solophp/validator checks them.
 */
final class InputServiceProvider
{
    public function register(WritableContainerInterface $container): void
    {
        // The application's own rules go here, by name: new Validator(['slug' => new SlugRule()])
        $container->set(ValidatorInterface::class, fn() => new Validator());

        // The container builds casters and processors that have dependencies of their own
        $container->set(InputFactory::class, fn(WritableContainerInterface $c) => new InputFactory(
            $c->get(ValidatorInterface::class),
            container: $c,
        ));
    }
}
