<?php

declare(strict_types=1);

namespace PortableSchema\Builder;

use PortableSchema\Registry;

/**
 * Writes `Types`: the type library (`types/v1`) as static methods, one per file, named after the id.
 */
final class TypesGenerator
{
    public static function source(): string
    {
        $methods = [];
        foreach (self::types() as $id => $schema) {
            $name = self::method($id);
            $title = addcslashes((string) ($schema->title ?? $id), '*/');
            $description = isset($schema->description) ? ' ' . addcslashes($schema->description, '*/') : '';
            $methods[] = <<<PHP

                /**
                 * {$title}: `{$id}`.{$description}
                 */
                public static function {$name}(): Schema
                {
                    return Schema::of('{$id}');
                }
            PHP;
        }
        $body = ltrim(implode("\n", $methods), "\n");
        return <<<PHP
        <?php

        declare(strict_types=1);

        namespace PortableSchema\Builder;

        /**
         * The type library by name: `Types::label()` is `Schema::of('portable:label')`.
         *
         * Generated from `types/v1` by `scripts/generate-types.php`. Do not edit; `TypesTest` holds it to the directory.
         */
        final class Types
        {
        {$body}
        }

        PHP;
    }

    /**
     * @return array<string, \stdClass> id to schema, by file name
     */
    public static function types(): array
    {
        $types = [];
        foreach (glob(Registry::TYPES_DIRECTORY . '/*.json') as $file) {
            $schema = json_decode(file_get_contents($file), flags: JSON_THROW_ON_ERROR);
            $types[$schema->{'$id'}] = $schema;
        }
        ksort($types);
        return $types;
    }

    /**
     * `portable:date-time` is `dateTime()`, `portable:uri-reference` is `uriReference()`.
     */
    public static function method(string $id): string
    {
        $name = substr($id, strlen(Registry::TYPES_NAMESPACE) + 1);
        return lcfirst(str_replace('-', '', ucwords($name, '-')));
    }
}
