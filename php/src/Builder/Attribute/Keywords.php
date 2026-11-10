<?php

declare(strict_types=1);

namespace PortableSchema\Builder\Attribute;

/**
 * Any keywords by name: `#[Keywords(title: 'Items per page', minimum: 1, maximum: 100)]`.
 *
 * The names are the schema's own (`minLength`, `x-portable-ui` as `ui`, `$ref` as `ref`, as the method names of
 * `Schema`). A schema-valued keyword takes a keyword map, a class name (read like a property type) or a boolean.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_PROPERTY)]
final class Keywords
{
    /** @var array<string, mixed> */
    public readonly array $keywords;

    public function __construct(mixed ...$keywords)
    {
        foreach (array_keys($keywords) as $name) {
            if (!is_string($name)) {
                throw new \InvalidArgumentException('Keywords are given by name.');
            }
        }
        $this->keywords = $keywords;
    }
}
