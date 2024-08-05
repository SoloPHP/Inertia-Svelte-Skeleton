<?php declare(strict_types=1);

use Solo\Application;
use Solo\Inertia\InertiaMiddleware;
use App\Middlewares\InertiaCommonPropsMiddleware;

return function (Application $app) {
    $app->addMiddleware(InertiaMiddleware::class);
    $app->addMiddleware(InertiaCommonPropsMiddleware::class);
};