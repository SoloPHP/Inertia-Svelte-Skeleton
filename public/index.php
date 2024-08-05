<?php declare(strict_types=1);

use Solo\Application;
use Solo\Container;
use Solo\Http\Emitter;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7Server\ServerRequestCreator;

define("ROOT_PATH", dirname(__DIR__));

require_once ROOT_PATH . '/vendor/autoload.php';

$container = new Container();
$services = require '../config/services.php';
$services($container);

$app = new Application($container);

$routes = require ROOT_PATH . '/config/routes.php';
$routes($app);

$middleware = require ROOT_PATH . '/config/middlewares.php';
$middleware($app);

$psr17Factory = new Psr17Factory();

$creator = new ServerRequestCreator(
    $psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory
);

$serverRequest = $creator->fromGlobals();

$response = $app->run($serverRequest);

$responseEmitter = new Emitter();
$responseEmitter->emit($response);