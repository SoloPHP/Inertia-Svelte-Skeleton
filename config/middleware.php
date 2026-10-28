<?php

declare(strict_types=1);

/*
 * Global middleware, outermost first. Each one is resolved from the container
 * only when a request reaches it.
 */

use App\Http\Middleware\CsrfMiddleware;
use App\Http\Middleware\ErrorMiddleware;
use App\Http\Middleware\JsonBodyMiddleware;
use App\Http\Middleware\SharedPropsMiddleware;
use Solo\Application\RoutingMiddleware;
use Solo\Inertia\InertiaMiddleware;

return [
    // The protocol rules for every response, the ones made below included: a 409 for a client built
    // from other assets, a 303 after PUT, PATCH and DELETE, redirects with a #fragment
    InertiaMiddleware::class,
    // Refuses a form sent without the XSRF-TOKEN the client echoes, and hands that token out
    CsrfMiddleware::class,
    // What every page gets: the application's name and the links of the layout
    SharedPropsMiddleware::class,
    // Turns every exception thrown below it into a page: validation errors go back to the form
    ErrorMiddleware::class,
    // The client sends forms as JSON: getParsedBody() then works as it does for HTML forms
    JsonBodyMiddleware::class,
    // Matches the route and runs its group and route middleware; 404 and 405 are thrown
    RoutingMiddleware::class,
];
