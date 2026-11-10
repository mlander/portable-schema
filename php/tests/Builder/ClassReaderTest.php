<?php

declare(strict_types=1);

namespace PortableSchema\Tests\Builder;

use PHPUnit\Framework\TestCase;
use PortableSchema\Builder\ClassReader;
use PortableSchema\Builder\Schema;
use PortableSchema\Registry;
use PortableSchema\Validator;

final class ClassReaderTest extends TestCase
{
    public function testAClassWithoutPropertiesIsATypeForOneValue(): void
    {
        self::assertSame(
            '{"$schema":"https://portable-schema.dev/v1/schema","$id":"drupal:surface_examples/phone","title":"Phone","description":"Digits, with spaces if you like, and a leading + for a country code.","$ref":"portable:string","pattern":"^\\\\+?[0-9 ]{7,20}$","examples":["+1 555 0100"]}',
            json_encode(ClassReader::read(PhoneNumber::class)->document(), JSON_UNESCAPED_SLASHES),
        );
    }

    public function testAClassWithPropertiesReferencesOtherTypesByClass(): void
    {
        $document = ClassReader::read(Contact::class)->document();
        self::assertSame('{"anyOf":[{"$ref":"drupal:surface_examples/phone"},{"type":"null"}],"default":null}', json_encode($document->properties->phone, JSON_UNESCAPED_SLASHES));
        self::assertSame('{"$ref":"drupal:surface_examples/phone"}', json_encode($document->properties->fax, JSON_UNESCAPED_SLASHES));
        self::assertSame(['name', 'fax'], $document->required);
    }

    public function testAClassIsReferencedByNameInFluentCalls(): void
    {
        self::assertSame('drupal:surface_examples/phone', Schema::of(PhoneNumber::class)->get('$ref'));
        self::assertSame('#/$defs/x', Schema::of('#/$defs/x')->get('$ref'));
        $this->expectException(\InvalidArgumentException::class);
        Schema::of(FeaturedItem::class);
    }

    public function testBothTypesLoadAndValidateTogether(): void
    {
        $registry = Registry::withTypes()
            ->add(ClassReader::read(PhoneNumber::class)->document())
            ->add(ClassReader::read(Contact::class)->document());
        $validator = new Validator($registry);
        self::assertTrue($validator->validate((object) ['name' => 'Ann', 'fax' => '+1 555 0100'], 'drupal:surface_examples/contact')->valid);
        $result = $validator->validate((object) ['name' => 'Ann', 'fax' => 'call me'], 'drupal:surface_examples/contact');
        self::assertSame('pattern', $result->errors[0]->code);
        self::assertSame('/fax', $result->errors[0]->instanceLocation);
    }

    public function testAClassDeclaresTheGroupsItsPropertiesName(): void
    {
        $document = ClassReader::read(PricedItem::class)->document();
        self::assertSame('{"groups":{"pricing":{"label":"Pricing","widget":"details","options":{"open":false}}}}', json_encode($document->{'x-portable-ui'}));
        self::assertSame('{"group":"pricing","weight":1}', json_encode($document->properties->price->{'x-portable-ui'}));
        self::assertObjectNotHasProperty('x-portable-ui', $document->properties->title);
        $validator = new Validator(Registry::withTypes()->add($document));
        self::assertTrue($validator->validate((object) ['title' => 'Tea', 'price' => 2.5], 'mymodule:priced_item')->valid);
    }
}
