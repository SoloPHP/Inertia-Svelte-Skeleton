<?php

declare(strict_types=1);

namespace App\Task;

use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use LogicException;
use Solo\Repository\Mapping;
use Solo\Repository\Repository;

/**
 * The `tasks` table. Repository gives the reads and writes; the subclass fixes
 * the mapping, so the container can inject a typed TaskRepository, and adds
 * what every write of a task needs: its timestamps.
 *
 * @extends Repository<Task>
 */
final readonly class TaskRepository extends Repository
{
    public function __construct(Connection $connection)
    {
        parent::__construct($connection, self::mapping());
    }

    /** @return Mapping<Task> */
    public static function mapping(): Mapping
    {
        return new Mapping(
            table: 'tasks',
            columns: [
                'id' => Types::INTEGER,
                'title' => Types::STRING,
                'done' => Types::BOOLEAN,
                'created_at' => Types::DATETIME_IMMUTABLE,
                'updated_at' => Types::DATETIME_IMMUTABLE,
            ],
            hydrate: self::hydrate(...),
        );
    }

    /** @param array<string, mixed> $row A row the column types above have already converted to PHP values */
    private static function hydrate(array $row): Task
    {
        /**
         * @var array{
         *     id: int,
         *     title: string,
         *     done: bool,
         *     created_at: DateTimeImmutable,
         *     updated_at: DateTimeImmutable,
         * } $row
         */
        return new Task(
            id: $row['id'],
            title: $row['title'],
            done: $row['done'],
            createdAt: $row['created_at'],
            updatedAt: $row['updated_at'],
        );
    }

    /** @param array<string, mixed> $fields */
    public function create(array $fields): Task
    {
        $now = new DateTimeImmutable();
        $id = $this->insertGetId($fields + ['created_at' => $now, 'updated_at' => $now]);

        return $this->read()->find($id) ?? throw new LogicException("Task $id is missing right after its insert");
    }

    /** @param array<string, mixed> $changes Only the fields to change */
    public function change(Task $task, array $changes): Task
    {
        if ($changes === []) {
            return $task;
        }

        $this->update($task->id, $changes + ['updated_at' => new DateTimeImmutable()]);

        return $this->read()->find($task->id) ?? throw new LogicException("Task $task->id is missing after its update");
    }
}
