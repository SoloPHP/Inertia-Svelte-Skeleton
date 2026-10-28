<?php

declare(strict_types=1);

// `composer serve` runs PHP's built-in server with this file as its router: a file that
// exists in public/, such as a built asset, is left to the server to send as it is
if (PHP_SAPI === 'cli-server') {
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $path = parse_url(is_string($uri) ? $uri : '/', PHP_URL_PATH);
    $file = is_string($path) ? realpath(__DIR__ . $path) : false;
    if ($file !== false && str_starts_with($file, __DIR__ . DIRECTORY_SEPARATOR) && is_file($file)) {
        return false;
    }
}

/** @var Solo\Application\Application $app */
$app = require __DIR__ . '/../bootstrap/app.php';

$app->run();
