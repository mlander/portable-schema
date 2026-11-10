<?php

declare(strict_types=1);

namespace PortableSchema\Tests\Builder;

use PHPUnit\Framework\TestCase;
use PortableSchema\Builder\Keywords;
use PortableSchema\Builder\Schema;
use PortableSchema\Json;

/**
 * The keyword table is the dialect: the 2020-12 vocabularies the meta-schema includes, minus what the profile
 * refuses, plus the Portable Schema vocabulary. Shapes come from the meta-schemas too.
 */
final class KeywordsTest extends TestCase
{
    private const RESOURCES = __DIR__ . '/../../resources/json-schema-2020-12';
    private const VOCAB = __DIR__ . '/../../../spec/v1/vocab.json';
    private const DIALECT = __DIR__ . '/../../../spec/v1/schema.json';

    public function testTheTableIsTheDialect(): void
    {
        $expected = [];
        $dialect = Json::decodeFile(self::DIALECT);
        foreach (array_keys((array) $dialect->{'$vocabulary'}) as $uri) {
            $file = str_starts_with($uri, 'https://json-schema.org/')
                ? self::RESOURCES . '/' . basename($uri) . '.json'
                : self::VOCAB;
            foreach ((array) Json::decodeFile($file)->properties as $keyword => $schema) {
                if (!in_array($keyword, Keywords::REFUSED, true)) {
                    $expected[$keyword] = self::shape($schema);
                }
            }
        }
        self::assertSame($expected, Keywords::TABLE);
    }

    public function testEveryKeywordHasOneMethod(): void
    {
        $docblock = (new \ReflectionClass(Schema::class))->getDocComment();
        $methods = [];
        foreach (array_keys(Keywords::TABLE) as $keyword) {
            $method = Keywords::method($keyword);
            self::assertSame($keyword, Keywords::keyword($method), "$method() stands for one keyword");
            self::assertArrayNotHasKey($method, $methods, "$method() is one keyword's method");
            $methods[$method] = $keyword;
            $declared = method_exists(Schema::class, $method) || str_contains($docblock, " self $method(");
            self::assertTrue($declared, "Schema::$method() exists or is declared in the docblock");
        }
    }

    public function testHostAnnotationsAreValues(): void
    {
        self::assertSame(Keywords::VALUE, Keywords::shape('x-surface'));
        self::assertNull(Keywords::shape('surface'));
        self::assertNull(Keywords::shape('label'));
    }

    private static function shape(\stdClass|bool $schema): string
    {
        if (is_bool($schema)) {
            return Keywords::VALUE;
        }
        if (isset($schema->{'$dynamicRef'})) {
            return Keywords::SCHEMA;
        }
        if (isset($schema->{'$ref'}) && str_ends_with($schema->{'$ref'}, '/schemaArray')) {
            return Keywords::SCHEMA_LIST;
        }
        if (isset($schema->additionalProperties->{'$dynamicRef'})) {
            return Keywords::SCHEMA_MAP;
        }
        return Keywords::VALUE;
    }
}
