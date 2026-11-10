<?php

declare(strict_types=1);

namespace PortableSchema\Builder\Attribute;

/**
 * The type of a property or class as a reference: `#[Of('portable:label')]` is `"$ref": "portable:label"`.
 *
 * On a property, the PHP type then only says whether the value is required and whether `null` is allowed.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_PROPERTY)]
final class Of
{
    public function __construct(public readonly string $ref)
    {
    }
}
