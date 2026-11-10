<?php

declare(strict_types=1);

namespace PortableSchema\Builder\Attribute;

/**
 * UI hints (`x-portable-ui`). On a property: `#[Ui(widget: 'time', group: 'details', weight: 10)]`. On a class, the
 * groups its properties name, declared once: `#[Ui(groups: ['details' => ['label' => 'Details', 'widget' => 'details']])]`.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_PROPERTY)]
final class Ui
{
    /**
     * @param array<string, mixed>|null $options
     * @param list<string>|null $fallback
     * @param array<string, array<string, mixed>>|null $groups a map from group name to `label`, `description`,
     *   `widget`, `options` and `weight`; `label` is required
     */
    public function __construct(
        public readonly ?string $widget = null,
        public readonly ?array $options = null,
        public readonly ?array $fallback = null,
        public readonly ?string $group = null,
        public readonly int|float|null $weight = null,
        public readonly ?array $groups = null,
    ) {
    }
}
