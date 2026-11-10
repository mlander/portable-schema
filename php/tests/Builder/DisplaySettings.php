<?php

declare(strict_types=1);

namespace PortableSchema\Tests\Builder;

use PortableSchema\Builder\Attribute\Document;
use PortableSchema\Builder\Attribute\Keywords;
use PortableSchema\Builder\Attribute\Of;

/**
 * The attribute spelling of fixtures/builder/display-settings.json.
 */
#[Document('mymodule:display_settings', title: 'Display settings')]
final class DisplaySettings
{
    #[Of('portable:label')]
    #[Keywords(title: 'Heading', description: 'Text shown above the listing.')]
    public string $heading = 'Latest articles';

    #[Of('portable:integer')]
    #[Keywords(title: 'Items per page', minimum: 1, maximum: 100)]
    public int $items_per_page = 20;

    #[Keywords(title: 'Show author')]
    public bool $show_author = true;

    #[Keywords(
        anyOf: [['type' => 'string', 'format' => 'uri'], ['type' => 'integer', 'minimum' => 1]],
        title: 'Logo',
        description: 'A logo URI or the ID of a managed media item.',
    )]
    public mixed $logo;

    #[Keywords(title: 'Featured items', items: FeaturedItem::class)]
    public array $featured_items = [];
}
