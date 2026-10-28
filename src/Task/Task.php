<?php

declare(strict_types=1);

namespace App\Task;

use DateTimeImmutable;

/**
 * A row of `tasks`, as TaskRepository reads it.
 */
final readonly class Task
{
    public function __construct(
        public int $id,
        public string $title,
        public bool $done,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
    ) {
    }
}
