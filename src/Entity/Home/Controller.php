<?php

namespace App\Entity\Home;

use Solo\Inertia;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class Controller
{
    public function __construct(
        private readonly Inertia $inertia,
    )
    {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $props = ['name' => 'SoloPHP'];
        return $this->inertia->render($request, $response, 'Home', $props);
    }
}