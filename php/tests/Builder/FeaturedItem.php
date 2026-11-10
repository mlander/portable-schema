<?php

declare(strict_types=1);

namespace PortableSchema\Tests\Builder;

use PortableSchema\Builder\Attribute\Schema;
use PortableSchema\Builder\Attribute\Of;

/**
 * An item of DisplaySettings::$featured_items. No #[Document], so it is written inline.
 */
final class FeaturedItem
{
    #[Of('portable:label')]
    public string $title;

    #[Schema(format: 'uri')]
    public string $url;
}
