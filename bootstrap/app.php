<?php

declare(strict_types=1);

/*
 * Builds the application: the web front controller, bin/migrate and the tests
 * all start here, so each of them runs the same providers, middleware and routes.
 */

use Solo\Application\Application;
use Solo\Application\Config;
use Solo\Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

$basePath = dirname(__DIR__);

// .env.local overrides .env; variables the server, the shell or phpunit.xml set override both
Dotenv::load($basePath, ['.env', '.env.local']);

date_default_timezone_set(Dotenv::string('APP_TIMEZONE', 'UTC'));

/** @var list<class-string> $providers */
$providers = require $basePath . '/config/providers.php';
/** @var list<class-string> $middleware */
$middleware = require $basePath . '/config/middleware.php';

return new Application(new Config(
    basePath: $basePath,
    routesPath: $basePath . '/config/routes.php',
    providers: $providers,
    middleware: $middleware,
    // Production keeps the built router in a file; delete it whenever the routes change
    routesCachePath: Dotenv::string('APP_ENV', 'production') === 'production'
        ? $basePath . '/storage/cache/routes.php'
        : null,
));
