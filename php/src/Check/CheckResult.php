<?php

declare(strict_types=1);

namespace PortableSchema\Check;

/**
 * The outcome of a check for one request: errors, or why it could not run.
 */
final class CheckResult
{
    /**
     * @param list<array{code: string, params: array<string, mixed>, message?: string, location?: string}> $errors
     *   `location`, when given, is a JSON Pointer relative to the request's location: the error is about the
     *   value there, e.g. `/amount` for a check on an object that refuses its `amount`.
     */
    public function __construct(
        public readonly array $errors = [],
        public readonly ?string $unchecked = null,
    ) {
    }

    public static function unchecked(string $reason): self
    {
        return new self([], $reason);
    }
}
