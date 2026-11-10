<?php

declare(strict_types=1);

namespace PortableSchema\Builder\Attribute;

/**
 * Lists the property in `required` although it has a default: the key must be present, the default only documents.
 *
 * Without it, a property with no default and a type that does not allow null is required; every other one is not.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Required
{
}
