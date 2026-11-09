<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\InputValidator;
use Psr\Log\LoggerInterface;
use Solo\Application\Config;
use Solo\Contracts\Container\WritableContainerInterface;
use Solo\Dotenv\Dotenv;
use Solo\Inertia\Contracts\RequestValidatorInterface;
use Solo\Inertia\CookieFlashStore;
use Solo\Inertia\Inertia;
use Solo\Inertia\PhpRootView;
use Solo\Input\InputFactory;
use Solo\Vite\Vite;

/**
 * Inertia: the root template of a first visit, the asset version a client must
 * match, and the cookie that carries validation errors and flash data to the
 * next page. The service holds configuration only; what belongs to a request
 * travels with the request.
 *
 * Also the validator PrecognitionMiddleware asks when a form is validated while
 * the user fills it in.
 */
final class InertiaServiceProvider
{
    public function register(WritableContainerInterface $container): void
    {
        // npm run build writes public/build; npm run dev keeps its address in public/hot
        $container->set(Vite::class, function (WritableContainerInterface $c): Vite {
            $public = $c->get(Config::class)->basePath . '/public';

            return new Vite("$public/build/.vite/manifest.json", '/build/', "$public/hot");
        });

        $container->set(Inertia::class, function (WritableContainerInterface $c): Inertia {
            $vite = $c->get(Vite::class);

            return new Inertia(
                new PhpRootView($c->get(Config::class)->basePath . '/resources/views/app.php', [
                    'vite' => $vite,
                    'appName' => Dotenv::string('APP_NAME', 'Solo'),
                ]),
                // A new build changes it, and a client still running the old one reloads the page
                version: $vite->version(),
                flash: new CookieFlashStore(Dotenv::required('APP_KEY')),
                logger: $c->get(LoggerInterface::class),
            );
        });

        $container->set(RequestValidatorInterface::class, fn(WritableContainerInterface $c) => new InputValidator(
            $c->get(InputFactory::class),
        ));
    }
}
