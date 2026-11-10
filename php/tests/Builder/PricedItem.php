<?php

declare(strict_types=1);

namespace PortableSchema\Tests\Builder;

use PortableSchema\Builder\Attribute\Document;
use PortableSchema\Builder\Attribute\Ui;

/**
 * Two properties in one group, declared once on the class, and one property outside any group.
 */
#[Document('mymodule:priced_item', title: 'Priced item')]
#[Ui(groups: ['pricing' => ['label' => 'Pricing', 'widget' => 'details', 'options' => ['open' => false]]])]
final class PricedItem
{
    public string $title;

    #[Ui(group: 'pricing', weight: 1)]
    public float $price = 0.0;

    #[Ui(group: 'pricing', weight: 2)]
    public string $currency = 'EUR';
}
