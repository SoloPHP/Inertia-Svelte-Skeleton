<?php

declare(strict_types=1);

namespace App\Task;

use App\Http\Exceptions\NotFoundException;
use App\Task\Inputs\TaskIndexInput;
use App\Task\Inputs\TaskStoreInput;
use App\Task\Inputs\TaskUpdateInput;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Solo\Inertia\Inertia;
use Solo\Input\InputFactory;
use Solo\Input\Inputs;
use Solo\Router\UrlGenerator;

/**
 * A thin controller: it reads the request into an input DTO, hands it to the
 * repository, and answers with a page or a redirect. Invalid input and missing
 * records are thrown, and ErrorMiddleware answers them: the form gets its errors
 * back, a missing task the Error page.
 *
 * Every URL comes from a route name, so the links and redirects follow the
 * routes when their paths change.
 *
 * @phpstan-import-type TaskProps from TaskResource
 */
final readonly class TaskController
{
    private const int PER_PAGE = 10;

    public function __construct(
        private Inertia $inertia,
        private InputFactory $inputs,
        private TaskRepository $tasks,
        private TaskResource $resource,
        private UrlGenerator $urls,
    ) {
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $input = $this->inputs->fromQuery(TaskIndexInput::class, $request);

        return $this->inertia->render($request, $response, 'Tasks/Index', [
            // A Closure runs only when the response sends its prop: the request for the stats skips the list
            'tasks' => fn() => $this->page($input),
            'filters' => $input->query() + ['q' => '', 'done' => ''],
            // Left out of the page and loaded by the client right after it, in a second request
            'stats' => Inertia::defer(fn() => [
                'total' => $this->tasks->read()->count([]),
                'done' => $this->tasks->read()->count(['done' => true]),
            ]),
            'urls' => [
                'index' => $this->urls->generate('tasks.index'),
                'store' => $this->urls->generate('tasks.store'),
            ],
        ]);
    }

    public function store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $input = $this->inputs->fromBody(TaskStoreInput::class, $request);
        $task = $this->tasks->create(Inputs::toArray($input));

        // Back to the list as it was, filters included
        return $this->inertia->back($request, $response, fallback: $this->urls->generate('tasks.index'), flash: [
            'success' => "Task \"$task->title\" created.",
        ]);
    }

    /** @param array{id: string} $params */
    public function edit(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $params,
    ): ResponseInterface {
        return $this->inertia->render($request, $response, 'Tasks/Edit', [
            'task' => $this->resource->make($this->find($params['id'])),
            'urls' => ['index' => $this->urls->generate('tasks.index')],
        ]);
    }

    /** @param array{id: string} $params */
    public function update(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $params,
    ): ResponseInterface {
        $task = $this->find($params['id']);
        $input = $this->inputs->fromBody(TaskUpdateInput::class, $request);

        // A partial input gives only the fields the client sent
        $task = $this->tasks->change($task, Inputs::toArray($input));

        // A 302 after a PATCH: InertiaMiddleware makes it a 303, so the browser follows it with a GET
        return $this->inertia->redirect($request, $response, $this->urls->generate('tasks.index'), flash: [
            'success' => "Task \"$task->title\" saved.",
        ]);
    }

    /** @param array{id: string} $params */
    public function toggle(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $params,
    ): ResponseInterface {
        $task = $this->find($params['id']);
        $this->tasks->change($task, ['done' => !$task->done]);

        // Back to the list as it was: same filters, same page
        return $this->inertia->back($request, $response, fallback: $this->urls->generate('tasks.index'));
    }

    /** @param array{id: string} $params */
    public function destroy(
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $params,
    ): ResponseInterface {
        $task = $this->find($params['id']);
        $this->tasks->delete($task->id);

        return $this->inertia->back($request, $response, fallback: $this->urls->generate('tasks.index'), flash: [
            'success' => "Task \"$task->title\" deleted.",
        ]);
    }

    private function find(string $id): Task
    {
        return $this->tasks->read()->find((int) $id) ?? throw new NotFoundException();
    }

    /**
     * One page of the list and the links around it
     *
     * @return array{data: list<TaskProps>, page: int, pages: int, previous: ?string, next: ?string}
     */
    private function page(TaskIndexInput $input): array
    {
        $criteria = $input->criteria();
        $pages = max(1, (int) ceil($this->tasks->read()->count($criteria) / self::PER_PAGE));
        // A page past the end, as after deleting the last task on it, shows the last one
        $page = min($input->page, $pages);

        return [
            'data' => $this->resource->collection($this->tasks->read()->findBy(
                $criteria,
                ['id' => 'DESC'],
                limit: self::PER_PAGE,
                offset: ($page - 1) * self::PER_PAGE,
            )),
            'page' => $page,
            'pages' => $pages,
            'previous' => $page > 1 ? $this->indexUrl($input, $page - 1) : null,
            'next' => $page < $pages ? $this->indexUrl($input, $page + 1) : null,
        ];
    }

    private function indexUrl(TaskIndexInput $input, int $page): string
    {
        return $this->urls->generate('tasks.index', query: $input->query() + ['page' => $page]);
    }
}
