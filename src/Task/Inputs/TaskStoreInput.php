<?php

declare(strict_types=1);

namespace App\Task\Inputs;

use Solo\Input\Attributes\Input;
use Solo\Input\Attributes\Validate;

/**
 * POST /tasks {"title": "Write the docs"}
 *
 * A field without a default is required; one with a default may be left out.
 */
#[Input]
final readonly class TaskStoreInput
{
    public function __construct(
        #[Validate('required|string|min:3|max:255')]
        public string $title,
        #[Validate('boolean')]
        public bool $done = false,
    ) {
    }
}
