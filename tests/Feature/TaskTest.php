<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Task\TaskRepository;
use Solo\Inertia\Header;
use Tests\TestCase;

final class TaskTest extends TestCase
{
    private function createTask(string $title, bool $done = false): int
    {
        return $this->app->container->get(TaskRepository::class)->create(['title' => $title, 'done' => $done])->id;
    }

    /**
     * The titles of the tasks a page lists
     *
     * @param array<string, mixed> $page
     * @return list<mixed>
     */
    private static function titles(array $page): array
    {
        $data = self::tasks($page)['data'];
        self::assertIsArray($data);

        return array_values(array_map(static fn(mixed $task) => is_array($task) ? $task['title'] : null, $data));
    }

    /**
     * The page's tasks prop: one page of the list, and the links around it
     *
     * @param array<string, mixed> $page
     * @return array<string, mixed>
     */
    private static function tasks(array $page): array
    {
        $tasks = self::props($page)['tasks'];
        self::assertIsArray($tasks);
        self::assertIsArray($tasks['data'] ?? null);
        /** @var array<string, mixed> $tasks */
        return $tasks;
    }

    public function testIndexListsTasksAndDefersTheStats(): void
    {
        $id = $this->createTask('Write the docs');
        $this->createTask('Ship it', done: true);

        $page = $this->page($this->inertia('GET', '/tasks'));

        self::assertSame('Tasks/Index', $page['component']);
        self::assertSame(['Ship it', 'Write the docs'], self::titles($page));
        self::assertSame(['default' => ['stats']], $page['deferredProps']);
        self::assertArrayNotHasKey('stats', self::props($page));

        $data = self::tasks($page)['data'];
        self::assertIsArray($data);
        self::assertSame([
            'id' => $id,
            'title' => 'Write the docs',
            'done' => false,
            'urls' => [
                'edit' => "/tasks/$id/edit",
                'update' => "/tasks/$id",
                'toggle' => "/tasks/$id/toggle",
                'destroy' => "/tasks/$id",
            ],
        ], $data[1]);
        self::assertSame(['index' => '/tasks', 'store' => '/tasks'], self::props($page)['urls']);

        // The client then asks for the deferred stats alone
        $stats = $this->page($this->inertia('GET', '/tasks', headers: [
            Header::PARTIAL_COMPONENT => 'Tasks/Index',
            Header::PARTIAL_DATA => 'stats',
        ]));
        self::assertSame(['total' => 2, 'done' => 1], self::props($stats)['stats']);
        self::assertArrayNotHasKey('tasks', self::props($stats));
    }

    public function testFiltersArePartialReloads(): void
    {
        $this->createTask('Write the docs');
        $this->createTask('Fix the docs', done: true);
        $this->createTask('50% off');

        $page = $this->page($this->inertia('GET', '/tasks?q=docs&done=0', headers: [
            Header::PARTIAL_COMPONENT => 'Tasks/Index',
            Header::PARTIAL_DATA => 'tasks,filters',
        ]));

        self::assertSame(['Write the docs'], self::titles($page));
        self::assertSame(['q' => 'docs', 'done' => '0'], self::props($page)['filters']);
        self::assertSame(['errors', 'tasks', 'filters'], array_keys(self::props($page)));

        // A "%" matches only itself, and a blank filter is no filter
        $percent = $this->inertia('GET', '/tasks?q=' . rawurlencode('%'));
        self::assertSame(['50% off'], self::titles($this->page($percent)));
        self::assertCount(3, self::titles($this->page($this->inertia('GET', '/tasks?q=&done='))));
    }

    public function testPagination(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            $this->createTask("Task $i");
        }

        $second = $this->page($this->inertia('GET', '/tasks?q=Task&page=2'));
        self::assertSame(['Task 2', 'Task 1'], self::titles($second));
        $tasks = self::tasks($second);
        unset($tasks['data']);
        self::assertSame(['page' => 2, 'pages' => 2, 'previous' => '/tasks?q=Task&page=1', 'next' => null], $tasks);

        // A page past the end shows the last one
        self::assertSame(['Task 2', 'Task 1'], self::titles($this->page($this->inertia('GET', '/tasks?page=9'))));

        // A page number no link makes is a bad request
        $invalid = $this->inertia('GET', '/tasks?page=0');
        self::assertSame(400, $invalid->getStatusCode());
        self::assertSame('Error', $this->page($invalid)['component']);
    }

    public function testCreatedTaskRedirectsWithAMessage(): void
    {
        $this->visit('/tasks');

        $redirect = $this->inertia('POST', '/tasks', ['title' => 'Write the docs']);
        self::assertSame(302, $redirect->getStatusCode());
        self::assertSame('/tasks', $redirect->getHeaderLine('Location'));

        $page = $this->page($this->follow($redirect));
        self::assertSame(['success' => 'Task "Write the docs" created.'], $page['flash']);
        self::assertSame(['Write the docs'], self::titles($page));
        self::assertSame([], self::props($page)['errors']);

        // Shown once
        self::assertArrayNotHasKey('flash', $this->page($this->inertia('GET', '/tasks')));
    }

    public function testInvalidTaskGoesBackToTheFormWithErrors(): void
    {
        $this->visit('/tasks?q=docs');

        $redirect = $this->inertia('POST', '/tasks', ['title' => '']);
        self::assertSame(302, $redirect->getStatusCode());
        self::assertSame('/tasks?q=docs', $redirect->getHeaderLine('Location'));

        $page = $this->page($this->follow($redirect));
        self::assertSame(['title' => 'The title field is required.'], self::props($page)['errors']);
        self::assertArrayNotHasKey('flash', $page);

        $tooLong = $this->follow($this->inertia('POST', '/tasks', ['title' => str_repeat('x', 256)]));
        self::assertSame(
            ['title' => 'The title may not be longer than 255 characters.'],
            self::props($this->page($tooLong))['errors'],
        );

        // The errors are shown once
        self::assertSame([], self::props($this->page($this->inertia('GET', '/tasks')))['errors']);
    }

    public function testErrorsGoUnderTheFormsErrorBag(): void
    {
        $this->visit('/tasks');
        $headers = [Header::ERROR_BAG => 'createTask'];

        $page = $this->page($this->follow($this->inertia('POST', '/tasks', ['title' => ''], $headers), $headers));

        self::assertSame(['createTask' => ['title' => 'The title field is required.']], self::props($page)['errors']);
    }

    public function testEditAndUpdate(): void
    {
        $id = $this->createTask('Old title');

        $edit = $this->page($this->inertia('GET', "/tasks/$id/edit"));
        self::assertSame('Tasks/Edit', $edit['component']);
        $task = self::props($edit)['task'];
        self::assertIsArray($task);
        self::assertSame('Old title', $task['title']);

        // A 302 after a PATCH is a 303, so the browser follows it with a GET
        $redirect = $this->inertia('PATCH', "/tasks/$id", ['title' => 'New title', 'done' => true]);
        self::assertSame(303, $redirect->getStatusCode());
        self::assertSame('/tasks', $redirect->getHeaderLine('Location'));

        $index = $this->page($this->follow($redirect));
        self::assertSame(['success' => 'Task "New title" saved.'], $index['flash']);
        self::assertSame(['New title'], self::titles($index));
    }

    public function testInvalidUpdateGoesBackToTheEditPage(): void
    {
        $id = $this->createTask('Title');
        $this->visit("/tasks/$id/edit");

        $redirect = $this->inertia('PATCH', "/tasks/$id", ['title' => '']);
        self::assertSame(303, $redirect->getStatusCode());
        self::assertSame("/tasks/$id/edit", $redirect->getHeaderLine('Location'));

        $page = $this->page($this->follow($redirect));
        self::assertSame('Tasks/Edit', $page['component']);
        self::assertSame(['title' => 'The title field is required.'], self::props($page)['errors']);
    }

    public function testToggleAndDeleteGoBackToTheListAsItWas(): void
    {
        $id = $this->createTask('Toggle me');
        $this->visit('/tasks?done=0');

        $toggled = $this->inertia('PATCH', "/tasks/$id/toggle");
        self::assertSame(303, $toggled->getStatusCode());
        self::assertSame('/tasks?done=0', $toggled->getHeaderLine('Location'));
        self::assertSame([], self::titles($this->page($this->follow($toggled))));

        $deleted = $this->inertia('DELETE', "/tasks/$id");
        self::assertSame(303, $deleted->getStatusCode());
        self::assertSame('/tasks?done=0', $deleted->getHeaderLine('Location'));
        self::assertSame(['success' => 'Task "Toggle me" deleted.'], $this->page($this->follow($deleted))['flash']);

        self::assertNull($this->app->container->get(TaskRepository::class)->read()->find($id));
    }

    public function testMissingTaskIsA404ErrorPage(): void
    {
        $this->visit('/tasks');

        $response = $this->inertia('GET', '/tasks/999/edit');
        self::assertSame(404, $response->getStatusCode());
        self::assertSame('Error', $this->page($response)['component']);

        self::assertSame(404, $this->inertia('PATCH', '/tasks/999/toggle')->getStatusCode());
        // {id:\d+} does not match, so this is the router's 404
        self::assertSame(404, $this->inertia('GET', '/tasks/abc/edit')->getStatusCode());
    }

    public function testFormWithoutTheXsrfTokenIsRefused(): void
    {
        // No page visited, so the browser has no XSRF-TOKEN cookie to echo
        $refused = $this->inertia('POST', '/tasks', ['title' => 'Forged'], ['Referer' => 'http://localhost/tasks']);

        self::assertSame(302, $refused->getStatusCode());
        self::assertSame('/tasks', $refused->getHeaderLine('Location'));
        self::assertStringContainsString('XSRF-TOKEN=', $refused->getHeaderLine('Set-Cookie'));

        $page = $this->page($this->follow($refused));
        self::assertSame(['error' => 'The page expired, please try again.'], $page['flash']);
        self::assertSame([], self::titles($page));

        // With the token the refused response handed out, the next try passes
        self::assertSame(302, $this->inertia('POST', '/tasks', ['title' => 'Real'])->getStatusCode());
        self::assertSame(['Real'], self::titles($this->page($this->inertia('GET', '/tasks'))));
    }

    public function testFormWithAWrongTokenIsRefused(): void
    {
        $this->visit('/tasks');

        $response = $this->inertia('POST', '/tasks', ['title' => 'Forged'], ['X-XSRF-TOKEN' => str_repeat('0', 64)]);

        self::assertSame(302, $response->getStatusCode());
        self::assertSame([], $this->app->container->get(TaskRepository::class)->read()->findBy([]));
    }
}
