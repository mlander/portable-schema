<?php

declare(strict_types=1);

namespace PortableSchema\Builder\Attribute;

/**
 * A named check on the property or class: `#[Check('drupal:UniqueField')]`. Repeatable.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
final class Check
{
    /**
     * @param array<string, mixed> $options
     */
    public function __construct(public readonly string $name, public readonly array $options = [])
    {
    }
}
