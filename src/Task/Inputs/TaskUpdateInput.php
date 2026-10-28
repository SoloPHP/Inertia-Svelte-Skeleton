<?php

declare(strict_types=1);

namespace App\Task\Inputs;

use Solo\Input\Attributes\Input;
use Solo\Input\Attributes\Validate;

/**
 * PATCH /tasks/{id} {"title": "Write the docs", "done": true}
 *
 * A partial input: Inputs::toArray() gives only the fields the client sent,
 * so the defaults below never overwrite what is stored. `filled` rejects a
 * field that is sent empty.
 */
#[Input(partial: true)]
final readonly class TaskUpdateInput
{
    public function __construct(
        #[Validate('filled|string|min:3|max:255')]
        public string $title = '',
        #[Validate('filled|boolean')]
        public bool $done = false,
    ) {
    }
}
