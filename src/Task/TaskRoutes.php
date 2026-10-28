<?php

declare(strict_types=1);

namespace App\Task;

use App\Task\Inputs\TaskStoreInput;
use App\Task\Inputs\TaskUpdateInput;
use Solo\Inertia\PrecognitionMiddleware;
use Solo\Router\RouteCollector;

final class TaskRoutes
{
    public function register(RouteCollector $r): void
    {
        // The forms validate while the user types: PrecognitionMiddleware checks such a request
        // with the input class its route names, and never runs the handler for it
        $r->group('/tasks', function (RouteCollector $r): void {
            $r->get('', [TaskController::class, 'index'])->name('tasks.index');
            $r->post('', [TaskController::class, 'store'])
                ->name('tasks.store')
                ->attribute('input', TaskStoreInput::class);
            $r->get('/{id:\d+}/edit', [TaskController::class, 'edit'])->name('tasks.edit');
            $r->patch('/{id:\d+}', [TaskController::class, 'update'])
                ->name('tasks.update')
                ->attribute('input', TaskUpdateInput::class);
            $r->patch('/{id:\d+}/toggle', [TaskController::class, 'toggle'])->name('tasks.toggle');
            $r->delete('/{id:\d+}', [TaskController::class, 'destroy'])->name('tasks.destroy');
        }, middlewares: [PrecognitionMiddleware::class]);
    }
}
