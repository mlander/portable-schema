<?php

declare(strict_types=1);

namespace PortableSchema\Builder\Attribute;

/**
 * UI hints for the property (`x-portable-ui`): `#[Ui(widget: 'time', group: 'details', weight: 10)]`.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class Ui
{
    /**
     * @param array<string, mixed>|null $options
     * @param list<string>|null $fallback
     */
    public function __construct(
        public readonly ?string $widget = null,
        public readonly ?array $options = null,
        public readonly ?array $fallback = null,
        public readonly ?string $group = null,
        public readonly int|float|null $weight = null,
    ) {
    }
}
