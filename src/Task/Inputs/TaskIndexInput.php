<?php

declare(strict_types=1);

namespace App\Task\Inputs;

use Solo\Input\Attributes\EmptyToNull;
use Solo\Input\Attributes\Input;
use Solo\Input\Attributes\Validate;
use Solo\Repository\Like;

/**
 * GET /tasks?q=docs&done=0&page=2
 *
 * Each filter defaults to null, and a filter the client did not send narrows
 * nothing. A filter sent blank, as in `?q=` or `?done=`, has no value either:
 * #[EmptyToNull] reads a blank string as null, and a blank bool is null anyway.
 */
#[Input]
final readonly class TaskIndexInput
{
    public function __construct(
        #[Validate('string|max:100')]
        #[EmptyToNull]
        public ?string $q = null,
        #[Validate('boolean')]
        public ?bool $done = null,
        #[Validate('integer|min_value:1')]
        public int $page = 1,
    ) {
    }

    /**
     * The filters as repository criteria. Like::contains() escapes the search
     * text, so a "%" or "_" the user types matches only itself.
     *
     * @return array<string, mixed>
     */
    public function criteria(): array
    {
        return array_filter([
            'title LIKE' => $this->q === null ? null : Like::contains($this->q),
            'done' => $this->done,
        ], static fn(mixed $value): bool => $value !== null);
    }

    /**
     * The filters as the query string of a link, such as the next page
     *
     * @return array{q?: string, done?: '0'|'1'}
     */
    public function query(): array
    {
        return array_filter([
            'q' => $this->q,
            'done' => $this->done === null ? null : ($this->done ? '1' : '0'),
        ], static fn(?string $value): bool => $value !== null);
    }
}
