<?php

declare(strict_types=1);

namespace PortableSchema\Tests\Builder;

use PortableSchema\Builder\Attribute\Document;
use PortableSchema\Builder\Attribute\Of;

/**
 * A type for an object: a class with properties. Surface API calls this a structure.
 */
#[Document('drupal:surface_examples/contact', title: 'Contact')]
final class Contact
{
    #[Of('portable:label')]
    public string $name;

    public ?PhoneNumber $phone = null;

    #[Of(PhoneNumber::class)]
    public string $fax;
}
