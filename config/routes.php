<?php declare(strict_types=1);

use Solo\Application;

return function (Application $app) {
    $app->get('/', [App\Entity\Home\Controller::class, 'index']);
};