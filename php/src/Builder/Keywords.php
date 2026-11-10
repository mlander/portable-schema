<?php

declare(strict_types=1);

namespace PortableSchema\Builder;

/**
 * The keywords a Portable Schema may hold, and the shape of each one's value.
 *
 * This table is the builder's whole vocabulary: a keyword method exists because the keyword is in the dialect
 * (the 2020-12 vocabularies `spec/v1/schema.json` includes, minus what the profile refuses, plus `spec/v1/vocab.json`).
 * `KeywordsTest` holds the table to those files, so the builder cannot know a keyword the validator does not.
 */
final class Keywords
{
    /** The value is a JSON value, kept as given. */
    public const VALUE = 'value';

    /** The value is one schema. */
    public const SCHEMA = 'schema';

    /** The value is a map from names to schemas. */
    public const SCHEMA_MAP = 'schemaMap';

    /** The value is a list of schemas. */
    public const SCHEMA_LIST = 'schemaList';

    /**
     * Keyword name to value shape. The order is the order of the vocabularies in the dialect meta-schema.
     *
     * @var array<string, string>
     */
    public const TABLE = [
        // core
        '$id' => self::VALUE,
        '$schema' => self::VALUE,
        '$anchor' => self::VALUE,
        '$ref' => self::VALUE,
        '$comment' => self::VALUE,
        '$defs' => self::SCHEMA_MAP,
        // applicator
        'prefixItems' => self::SCHEMA_LIST,
        'items' => self::SCHEMA,
        'contains' => self::SCHEMA,
        'additionalProperties' => self::SCHEMA,
        'properties' => self::SCHEMA_MAP,
        'patternProperties' => self::SCHEMA_MAP,
        'dependentSchemas' => self::SCHEMA_MAP,
        'propertyNames' => self::SCHEMA,
        'if' => self::SCHEMA,
        'then' => self::SCHEMA,
        'else' => self::SCHEMA,
        'allOf' => self::SCHEMA_LIST,
        'anyOf' => self::SCHEMA_LIST,
        'oneOf' => self::SCHEMA_LIST,
        'not' => self::SCHEMA,
        // unevaluated
        'unevaluatedItems' => self::SCHEMA,
        'unevaluatedProperties' => self::SCHEMA,
        // validation
        'multipleOf' => self::VALUE,
        'maximum' => self::VALUE,
        'exclusiveMaximum' => self::VALUE,
        'minimum' => self::VALUE,
        'exclusiveMinimum' => self::VALUE,
        'maxLength' => self::VALUE,
        'minLength' => self::VALUE,
        'pattern' => self::VALUE,
        'maxItems' => self::VALUE,
        'minItems' => self::VALUE,
        'uniqueItems' => self::VALUE,
        'maxContains' => self::VALUE,
        'minContains' => self::VALUE,
        'maxProperties' => self::VALUE,
        'minProperties' => self::VALUE,
        'required' => self::VALUE,
        'dependentRequired' => self::VALUE,
        'const' => self::VALUE,
        'enum' => self::VALUE,
        'type' => self::VALUE,
        // meta-data
        'title' => self::VALUE,
        'description' => self::VALUE,
        'default' => self::VALUE,
        'deprecated' => self::VALUE,
        'readOnly' => self::VALUE,
        'writeOnly' => self::VALUE,
        'examples' => self::VALUE,
        // format-assertion
        'format' => self::VALUE,
        // content
        'contentMediaType' => self::VALUE,
        'contentEncoding' => self::VALUE,
        'contentSchema' => self::SCHEMA,
        // vocab
        'portable-typeBy' => self::VALUE,
        'portable-refine' => self::VALUE,
        'portable-check' => self::VALUE,
        'portable-compare' => self::VALUE,
        'x-portable-type' => self::VALUE,
        'x-portable-translatable' => self::VALUE,
        'x-portable-provider' => self::VALUE,
        'x-portable-ui' => self::VALUE,
        'x-portable-links' => self::VALUE,
        'x-portable-messages' => self::VALUE,
        'x-portable-unchecked' => self::VALUE,
    ];

    /**
     * Keywords the profile refuses in every schema (spec/profile.md), although their vocabulary is in the dialect.
     */
    public const REFUSED = ['$vocabulary', '$dynamicRef', '$dynamicAnchor'];

    /**
     * The shape of $keyword's value, or null for a keyword outside the vocabulary.
     *
     * A host annotation (`x-` followed by anything) is a value: the profile reads every `x-` keyword as an annotation.
     */
    public static function shape(string $keyword): ?string
    {
        if (isset(self::TABLE[$keyword])) {
            return self::TABLE[$keyword];
        }
        return str_starts_with($keyword, 'x-') ? self::VALUE : null;
    }

    /**
     * The method name of a keyword: `$ref` is `ref()`, `portable-check` is `check()`, `x-portable-ui` is `ui()`.
     */
    public static function method(string $keyword): string
    {
        if ($keyword === 'x-portable-type') {
            // `type` is the JSON type keyword's method.
            return 'portableType';
        }
        $name = preg_replace('/^(\$|portable-|x-portable-)/', '', $keyword);
        return lcfirst(str_replace('-', '', ucwords($name, '-')));
    }

    /**
     * The keyword a method name stands for, or null.
     */
    public static function keyword(string $method): ?string
    {
        static $byMethod = null;
        if ($byMethod === null) {
            $byMethod = [];
            foreach (array_keys(self::TABLE) as $keyword) {
                $byMethod[self::method($keyword)] ??= $keyword;
            }
        }
        return $byMethod[$method] ?? null;
    }
}
