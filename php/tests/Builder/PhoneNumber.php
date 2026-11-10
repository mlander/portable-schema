<?php

declare(strict_types=1);

namespace PortableSchema\Tests\Builder;

use PortableSchema\Builder\Attribute\Document;
use PortableSchema\Builder\Attribute\Schema;
use PortableSchema\Builder\Attribute\Of;

/**
 * A type for one value: a class without properties. Surface API calls this a shape.
 */
#[Document('drupal:surface_examples/phone', title: 'Phone', description: 'Digits, with spaces if you like, and a leading + for a country code.')]
#[Of('portable:string')]
#[Schema(pattern: '^\+?[0-9 ]{7,20}$', examples: ['+1 555 0100'])]
final class PhoneNumber
{
}
