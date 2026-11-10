<?php

declare(strict_types=1);

namespace PortableSchema\Tests\Builder;

use PHPUnit\Framework\TestCase;
use PortableSchema\Builder\Types;
use PortableSchema\Builder\TypesGenerator;

final class TypesTest extends TestCase
{
    public function testTheGeneratedFileIsCurrent(): void
    {
        self::assertSame(TypesGenerator::source(), file_get_contents(__DIR__ . '/../../src/Builder/Types.php'), 'Run php scripts/generate-types.php');
    }

    public function testEveryTypeHasAMethod(): void
    {
        foreach (array_keys(TypesGenerator::types()) as $id) {
            $method = TypesGenerator::method($id);
            self::assertTrue(method_exists(Types::class, $method), "$id has Types::$method()");
            self::assertSame($id, Types::$method()->get('$ref'));
        }
        self::assertSame('{"$ref":"portable:date-time"}', json_encode(Types::dateTime()->toDocument()));
    }
}
