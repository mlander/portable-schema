<?php

declare(strict_types=1);

namespace PortableSchema\Tests\Builder;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use PortableSchema\Builder\ClassReader;
use PortableSchema\Builder\Location;
use PortableSchema\Builder\Schema;
use PortableSchema\Builder\Types;
use PortableSchema\Json;
use PortableSchema\Registry;
use PortableSchema\Validator;

final class SchemaTest extends TestCase
{
    private const FIXTURE = __DIR__ . '/../../../fixtures/builder/display-settings.json';

    /**
     * The fluent calls of the fixture.
     */
    private static function displaySettings(): Schema
    {
        return Schema::object([
            'heading' => Types::label()
                ->title('Heading')
                ->description('Text shown above the listing.')
                ->default('Latest articles'),
            'items_per_page' => Types::integer()
                ->title('Items per page')
                ->minimum(1)
                ->maximum(100)
                ->default(20),
            'show_author' => Schema::bool()
                ->title('Show author')
                ->default(true),
            'logo' => Schema::anyOf([
                Schema::string()->format('uri'),
                Schema::integer()->minimum(1),
            ])
                ->title('Logo')
                ->description('A logo URI or the ID of a managed media item.'),
            'featured_items' => Schema::list(
                Schema::object([
                    'title' => Types::label()->required(),
                    'url' => Schema::string()->format('uri')->required(),
                ])->unevaluatedProperties(false)
            )
                ->title('Featured items')
                ->default([]),
        ])
            ->unevaluatedProperties(false)
            ->id('mymodule:display_settings')
            ->title('Display settings');
    }

    public function testTheFluentCallsProduceTheFixture(): void
    {
        self::assertSame(self::canonical(Json::decodeFile(self::FIXTURE)), self::canonical(self::displaySettings()->document()));
    }

    public function testTheAttributeClassProducesTheFixture(): void
    {
        self::assertSame(self::canonical(Json::decodeFile(self::FIXTURE)), self::canonical(ClassReader::read(DisplaySettings::class)->document()));
    }

    public function testTheDocumentLoadsAndValidates(): void
    {
        $registry = Registry::withTypes()->add(self::displaySettings()->document());
        $validator = new Validator($registry);

        $valid = $validator->validate((object) ['heading' => 'News', 'items_per_page' => 5, 'show_author' => false, 'logo' => 3], 'mymodule:display_settings');
        self::assertTrue($valid->valid);

        $invalid = $validator->validate((object) ['heading' => 'News', 'items_per_page' => 0, 'show_author' => false, 'logo' => 'not a uri'], 'mymodule:display_settings');
        self::assertFalse($invalid->valid);
        self::assertSame(['/items_per_page' => 'minimum', '/logo' => 'anyOf'], array_combine(
            array_column($invalid->errors, 'instanceLocation'),
            array_column($invalid->errors, 'code'),
        ));
    }

    public function testTheDocumentHeadIsOrdered(): void
    {
        $document = Schema::string()->title('T')->id('test:a')->document();
        self::assertSame(['$schema', '$id', 'title', 'type'], array_keys((array) $document));
        self::assertSame(Schema::DIALECT, $document->{'$schema'});
    }

    /**
     * Every schema of the repository reads into a Schema and writes back byte for byte: the builder is 1:1 to the document.
     */
    #[DataProvider('schemas')]
    public function testASchemaReadsAndWritesBackUnchanged(string $file): void
    {
        $document = Json::decodeFile($file);
        $expected = json_encode($document, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        self::assertSame($expected, Schema::from($document)->toJson(), $file);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function schemas(): iterable
    {
        $root = realpath(__DIR__ . '/../../..');
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root . '/types', \FilesystemIterator::SKIP_DOTS));
        $examples = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root . '/fixtures/examples', \FilesystemIterator::SKIP_DOTS));
        foreach ([$iterator, $examples] as $files) {
            foreach ($files as $file) {
                $path = $file->getPathname();
                if (str_ends_with($path, '.json') && !str_ends_with($path, '.test.json') && (str_contains($path, '/schemas/') || str_contains($path, '/types/'))) {
                    yield substr($path, strlen($root) + 1) => [$path];
                }
            }
        }
    }

    public function testRequiredIsCollectedByTheObject(): void
    {
        $object = Schema::object(['a' => Schema::string()->required(), 'b' => Schema::string()])
            ->property('c', Schema::integer()->required());
        self::assertSame(['a', 'c'], $object->get('required'));
        self::assertFalse($object->get('properties')['a']->has('required'));
        self::assertSame(['x', 'y'], Schema::object()->required('x', 'y')->get('required'));
    }

    public function testOptionsAreALabeledOneOf(): void
    {
        $document = Schema::options(['visible' => 'Visible', 'hidden' => 'Hidden'])->toDocument();
        self::assertSame('[{"const":"visible","title":"Visible"},{"const":"hidden","title":"Hidden"}]', json_encode($document->oneOf));
    }

    public function testChecksMerge(): void
    {
        $schema = Types::integer()->check('drupal:ValidReference')->check('drupal:ReferenceAccess', ['operation' => 'view']);
        self::assertSame('{"drupal:ValidReference":{},"drupal:ReferenceAccess":{"operation":"view"}}', json_encode($schema->toDocument()->{'portable-check'}));
    }

    public function testTypeByAndLocations(): void
    {
        $settings = Schema::of('drupal:block_settings')->typeBy('drupal:block.settings.{plugin}', ['plugin' => Location::sibling('plugin')], 'drupal:block.settings.*');
        self::assertSame(
            '{"href":"drupal:block.settings.{plugin}","source":{"plugin":"1/plugin"},"fallback":"drupal:block.settings.*"}',
            self::json($settings->toDocument()->{'portable-typeBy'}),
        );
        self::assertSame('0/id', Location::own('id'));
        self::assertSame('0#', Location::name());
        self::assertSame('2/a~1b/c', Location::up(2, 'a/b', 'c'));
        self::assertSame('3/x', Location::pointer('3/x'));
        $this->expectException(\InvalidArgumentException::class);
        Location::pointer('../x');
    }

    public function testWhenIsAConditionalInAllOf(): void
    {
        $object = Schema::object(['public' => Schema::bool(), 'served_at' => Schema::string()])
            ->when(['public' => true], Schema::any()->required('served_at'));
        self::assertSame(
            '[{"if":{"properties":{"public":{"const":true}},"required":["public"]},"then":{"required":["served_at"]}}]',
            json_encode($object->toDocument()->allOf),
        );
    }

    public function testUiLinksAndMessages(): void
    {
        $schema = Schema::string()->ui(widget: 'time')->ui(group: 'details', weight: 5)
            ->link('options', '/api/options{?q}', ['q' => Location::own('query')])
            ->message('minLength', 'Give a name.');
        $document = $schema->toDocument();
        self::assertSame('{"widget":"time","group":"details","weight":5}', json_encode($document->{'x-portable-ui'}));
        self::assertSame('[{"rel":"options","href":"/api/options{?q}","source":{"q":"0/query"}}]', self::json($document->{'x-portable-links'}));
        self::assertSame('{"minLength":"Give a name."}', json_encode($document->{'x-portable-messages'}));
    }

    public function testAHostAnnotationIsAnXKeyword(): void
    {
        self::assertSame('{"type":"string","x-surface":{"locked":true}}', json_encode(Schema::string()->annotation('x-surface', ['locked' => true])->toDocument()));
        $this->expectException(\InvalidArgumentException::class);
        Schema::string()->annotation('surface', []);
    }

    public function testAKeywordOutsideTheVocabularyIsRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('label is not a Portable Schema keyword');
        Schema::string()->with('label', 'Heading');
    }

    public function testARefusedKeywordIsRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Schema::string()->with('$dynamicRef', '#meta');
    }

    public function testAMethodOutsideTheVocabularyIsRefused(): void
    {
        $this->expectException(\BadMethodCallException::class);
        Schema::string()->label('Heading');
    }

    public function testASchemaNeverChanges(): void
    {
        $string = Schema::string();
        $string->minLength(1);
        self::assertFalse($string->has('minLength'));
    }

    public function testTranslatableStringsRenderOnWrite(): void
    {
        $title = new class () implements \Stringable {
            public function __toString(): string
            {
                return 'Rendered';
            }
        };
        self::assertSame('Rendered', Schema::string()->title($title)->toDocument()->title);
    }

    public function testBooleanSchemas(): void
    {
        self::assertSame('{"type":"object","properties":{"gone":false}}', json_encode(Schema::object(['gone' => false])->toDocument()));
        self::assertFalse(Schema::from(false)->toDocument());
    }

    private static function json(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_SLASHES);
    }

    /**
     * JSON with keys sorted at every level, for comparing documents built in a different order.
     */
    private static function canonical(mixed $document): string
    {
        $sort = static function (mixed $value) use (&$sort): mixed {
            if ($value instanceof \stdClass) {
                $array = (array) $value;
                ksort($array);
                return (object) array_map($sort, $array);
            }
            return is_array($value) ? array_map($sort, $value) : $value;
        };
        return json_encode($sort($document), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
