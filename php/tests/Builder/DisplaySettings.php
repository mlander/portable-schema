<?php

declare(strict_types=1);

namespace PortableSchema\Tests\Builder;

use PortableSchema\Builder\Attribute\Document;
use PortableSchema\Builder\Attribute\Schema;
use PortableSchema\Builder\Attribute\Of;

/**
 * The attribute spelling of fixtures/builder/display-settings.json.
 */
#[Document('mymodule:display_settings', title: 'Display settings')]
final class DisplaySettings
{
    #[Of('portable:label')]
    #[Schema(title: 'Heading', description: 'Text shown above the listing.')]
    public string $heading = 'Latest articles';

    #[Of('portable:integer')]
    #[Schema(title: 'Items per page', minimum: 1, maximum: 100)]
    public int $items_per_page = 20;

    #[Schema(title: 'Show author')]
    public bool $show_author = true;

    #[Schema(
        anyOf: [['type' => 'string', 'format' => 'uri'], ['type' => 'integer', 'minimum' => 1]],
        title: 'Logo',
        description: 'A logo URI or the ID of a managed media item.',
    )]
    public mixed $logo;

    #[Schema(title: 'Featured items', items: FeaturedItem::class)]
    public array $featured_items = [];
}
