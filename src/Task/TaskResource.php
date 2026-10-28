<?php

declare(strict_types=1);

namespace App\Task;

use Solo\Router\UrlGenerator;

/**
 * How a task looks in a page's props. The model stays free of presentation, and
 * each task brings the URLs of what can be done with it, made from route names:
 * the pages never build a path themselves.
 *
 * @phpstan-type TaskProps array{
 *     id: int,
 *     title: string,
 *     done: bool,
 *     urls: array{edit: string, update: string, toggle: string, destroy: string},
 * }
 */
final readonly class TaskResource
{
    public function __construct(private UrlGenerator $urls)
    {
    }

    /** @return TaskProps */
    public function make(Task $task): array
    {
        $id = ['id' => $task->id];

        return [
            'id' => $task->id,
            'title' => $task->title,
            'done' => $task->done,
            'urls' => [
                'edit' => $this->urls->generate('tasks.edit', $id),
                'update' => $this->urls->generate('tasks.update', $id),
                'toggle' => $this->urls->generate('tasks.toggle', $id),
                'destroy' => $this->urls->generate('tasks.destroy', $id),
            ],
        ];
    }

    /**
     * @param list<Task> $tasks
     * @return list<TaskProps>
     */
    public function collection(array $tasks): array
    {
        return array_map($this->make(...), $tasks);
    }
}
