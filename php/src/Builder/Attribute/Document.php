<?php

declare(strict_types=1);

namespace PortableSchema\Builder\Attribute;

/**
 * Makes a class a schema of its own: `$id`, `title` and `description` of the root document.
 *
 * A class with an id is referenced (`$ref`) where another class uses it as a property type; a class without one is
 * written inline.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class Document
{
    public function __construct(
        public readonly ?string $id = null,
        public readonly string|\Stringable|null $title = null,
        public readonly string|\Stringable|null $description = null,
    ) {
    }
}
