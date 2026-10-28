<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Task\TaskRepository;
use Solo\Inertia\Header;
use Tests\TestCase;

/**
 * The task forms validate while the user types: such a request is checked with the
 * input class of its route, and never reaches the handler.
 */
final class PrecognitionTest extends TestCase
{
    private function tasks(): TaskRepository
    {
        return $this->app->container->get(TaskRepository::class);
    }

    public function testInvalidFieldIsA422WithItsMessage(): void
    {
        $this->visit('/tasks');

        $response = $this->precognition('POST', '/tasks', ['title' => 'ab'], ['title']);

        self::assertSame(422, $response->getStatusCode());
        self::assertSame('true', $response->getHeaderLine(Header::PRECOGNITION));
        self::assertSame(
            ['errors' => ['title' => 'The title must be at least 3 characters long.']],
            self::decode((string) $response->getBody()),
        );
        self::assertSame([], $this->tasks()->read()->findBy([]));
    }

    public function testValidFieldIsA204AndNothingIsSaved(): void
    {
        $this->visit('/tasks');

        $response = $this->precognition('POST', '/tasks', ['title' => 'Write the docs'], ['title']);

        self::assertSame(204, $response->getStatusCode());
        self::assertSame('true', $response->getHeaderLine(Header::PRECOGNITION));
        self::assertSame('true', $response->getHeaderLine(Header::PRECOGNITION_SUCCESS));
        self::assertSame([], $this->tasks()->read()->findBy([]));
    }

    public function testOnlyTheFieldsAskedAboutCount(): void
    {
        $this->visit('/tasks');

        $done = $this->precognition('POST', '/tasks', ['title' => '', 'done' => 'maybe'], ['done']);
        self::assertSame(
            ['errors' => ['done' => 'The done must be yes or no.']],
            self::decode((string) $done->getBody()),
        );

        // The title is still empty, but the client did not ask about it
        $valid = $this->precognition('POST', '/tasks', ['title' => '', 'done' => true], ['done']);
        self::assertSame(204, $valid->getStatusCode());
    }

    public function testEditFormValidatesWithTheUpdateRules(): void
    {
        $id = $this->tasks()->create(['title' => 'Old title'])->id;
        $this->visit("/tasks/$id/edit");

        $response = $this->precognition('PATCH', "/tasks/$id", ['title' => '', 'done' => false], ['title']);

        self::assertSame(422, $response->getStatusCode());
        self::assertSame(
            ['errors' => ['title' => 'The title field is required.']],
            self::decode((string) $response->getBody()),
        );
        self::assertSame('Old title', $this->tasks()->read()->find($id)?->title);
    }

    public function testRouteWithoutAnInputClassFailsInsteadOfRunning(): void
    {
        $id = $this->tasks()->create(['title' => 'Stay as you are'])->id;
        $this->visit('/tasks');

        $response = $this->precognition('PATCH', "/tasks/$id/toggle", []);

        self::assertSame(500, $response->getStatusCode());
        self::assertFalse($this->tasks()->read()->find($id)?->done);
    }

    public function testFormRoutesVaryByPrecognition(): void
    {
        self::assertStringContainsString('Precognition', $this->visit('/tasks')->getHeaderLine('Vary'));
    }
}
