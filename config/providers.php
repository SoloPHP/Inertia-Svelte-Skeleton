<?php

declare(strict_types=1);

/*
 * Service providers fill the container, in this order, before the first request.
 * Anything not registered here is autowired by class name.
 */

use App\Providers\DatabaseServiceProvider;
use App\Providers\HttpServiceProvider;
use App\Providers\InertiaServiceProvider;
use App\Providers\InputServiceProvider;
use App\Providers\LoggingServiceProvider;

return [
    HttpServiceProvider::class,
    LoggingServiceProvider::class,
    DatabaseServiceProvider::class,
    InputServiceProvider::class,
    InertiaServiceProvider::class,
];
