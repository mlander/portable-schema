<?php

declare(strict_types=1);

namespace PortableSchema\Builder;

use PortableSchema\Builder\Attribute\Check;
use PortableSchema\Builder\Attribute\Document;
use PortableSchema\Builder\Attribute\Schema as SchemaAttribute;
use PortableSchema\Builder\Attribute\Of;
use PortableSchema\Builder\Attribute\Required;
use PortableSchema\Builder\Attribute\Ui;

/**
 * Reads a class whose public properties carry attributes into the same Schema the fluent calls would build.
 *
 * A class with public properties is an object schema; a class without any is a schema for one value, said by its
 * `#[Of]` and `#[Keywords]`. Both are types once they carry a `#[Document]` id. Each public property is a property
 * of the object, in declaration order:
 * - its name is the property's name;
 * - its type comes from `#[Of]`, else from the PHP type: `string`, `int`, `float`, `bool`, `array` (`items` from
 *   `#[Schema(items: ...)]`), a backed enum (`enum` of its values), or a class (read the same way: referenced by its
 *   `#[Document]` id, else written inline);
 * - `?T` allows null; a nullable `#[Of]` becomes `anyOf` of the reference and `null`;
 * - an initializer is `default`;
 * - it is required when it has no default and does not allow null, or carries `#[Required]`;
 * - `#[Schema]`, `#[Check]` and `#[Ui]` add the rest.
 *
 * The object is closed (`unevaluatedProperties: false`): a class is a concrete type. `#[Schema(unevaluatedProperties: true)]`
 * on the class opens it.
 */
final class ClassReader
{
    private const SCALARS = ['string' => 'string', 'int' => 'integer', 'float' => 'number', 'bool' => 'boolean', 'array' => 'array', 'null' => 'null'];

    /**
     * The schema of $class, as a root document when it carries `#[Document]`.
     *
     * @param class-string $class
     */
    public static function read(string $class): Schema
    {
        $reflection = new \ReflectionClass($class);
        $document = self::attribute($reflection, Document::class);
        $schema = Schema::any();
        if ($document?->id !== null) {
            $schema = $schema->with('$id', $document->id);
        }
        if ($document?->title !== null) {
            $schema = $schema->with('title', $document->title);
        }
        if ($document?->description !== null) {
            $schema = $schema->with('description', $document->description);
        }
        $of = self::attribute($reflection, Of::class);
        if ($of !== null) {
            $schema = $schema->with('$ref', self::ref($of->ref));
        }
        $properties = [];
        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            if (!$property->isStatic()) {
                $properties[$property->getName()] = self::property($property);
            }
        }
        if ($properties === []) {
            // One value: what the attributes say, and nothing else.
            return self::common($schema, $reflection);
        }
        if ($of === null) {
            $schema = $schema->with('type', 'object');
        }
        $schema = self::common($schema->properties($properties), $reflection);
        if (!$schema->has('unevaluatedProperties')) {
            $schema = $schema->with('unevaluatedProperties', false);
        }
        return $schema;
    }

    private static function property(\ReflectionProperty $property): Schema
    {
        $type = $property->getType();
        $of = self::attribute($property, Of::class);
        $nullable = $type?->allowsNull() ?? true;
        if ($of !== null) {
            $schema = Schema::of(self::ref($of->ref));
            if ($nullable && $type !== null) {
                $schema = Schema::anyOf([$schema, Schema::null()]);
            }
        } else {
            $schema = self::typed($type, $property);
        }
        $schema = self::common($schema, $property);
        if ($property->hasDefaultValue()) {
            $schema = $schema->with('default', self::value($property->getDefaultValue()));
        }
        $required = self::attribute($property, Required::class) !== null
            || (!$property->hasDefaultValue() && !$nullable);
        $ui = self::attribute($property, Ui::class);
        if ($ui !== null) {
            $schema = $schema->ui($ui->widget, $ui->options, $ui->fallback, $ui->group, $ui->weight);
        }
        return $required ? $schema->required() : $schema;
    }

    private static function typed(?\ReflectionType $type, \ReflectionProperty $property): Schema
    {
        if ($type === null) {
            return Schema::any();
        }
        $names = $type instanceof \ReflectionUnionType
            ? array_map(fn (\ReflectionNamedType $t) => $t->getName(), $type->getTypes())
            : [$type->getName()];
        if ($type->allowsNull() && !in_array('null', $names, true)) {
            $names[] = 'null';
        }
        $json = [];
        $schemas = [];
        foreach ($names as $name) {
            if ($name === 'mixed') {
                return Schema::any();
            }
            if (isset(self::SCALARS[$name])) {
                $json[] = self::SCALARS[$name];
            } elseif (enum_exists($name)) {
                $schemas[] = Schema::enum(array_map(fn (\BackedEnum $case) => $case->value, $name::cases()));
            } elseif (class_exists($name)) {
                $schemas[] = self::nested($name);
            } else {
                throw new \InvalidArgumentException("The property {$property->getName()} has the type $name, which is not a schema.");
            }
        }
        if ($schemas === []) {
            return Schema::any()->with('type', count($json) === 1 ? $json[0] : $json);
        }
        if ($json !== []) {
            $schemas[] = Schema::any()->with('type', count($json) === 1 ? $json[0] : $json);
        }
        return count($schemas) === 1 ? $schemas[0] : Schema::anyOf($schemas);
    }

    /**
     * The `#[Document]` id of a class given by name, or null for anything else.
     *
     * @throws \InvalidArgumentException for a class without an id: it cannot be referenced, only read inline
     */
    public static function id(string $class): ?string
    {
        if (!class_exists($class)) {
            return null;
        }
        $id = self::attribute(new \ReflectionClass($class), Document::class)?->id;
        if ($id === null) {
            throw new \InvalidArgumentException("$class has no #[Document] id to reference.");
        }
        return $id;
    }

    /**
     * A reference as given, or the `#[Document]` id of a class given by name: `#[Of(PhoneNumber::class)]`.
     */
    private static function ref(string $ref): string
    {
        return self::id($ref) ?? $ref;
    }

    /**
     * @param class-string $class
     */
    private static function nested(string $class): Schema
    {
        $id = self::attribute(new \ReflectionClass($class), Document::class)?->id;
        return $id === null ? self::read($class) : Schema::of($id);
    }

    private static function common(Schema $schema, \ReflectionClass|\ReflectionProperty $target): Schema
    {
        foreach ($target->getAttributes(SchemaAttribute::class) as $attribute) {
            foreach ($attribute->newInstance()->keywords as $method => $value) {
                $keyword = Keywords::keyword($method) ?? $method;
                if (Keywords::shape($keyword) !== Keywords::VALUE && is_string($value) && class_exists($value)) {
                    $value = self::nested($value);
                }
                $schema = $schema->with($keyword, $value);
            }
        }
        foreach ($target->getAttributes(Check::class) as $attribute) {
            $check = $attribute->newInstance();
            $schema = $schema->check($check->name, $check->options);
        }
        return $schema;
    }

    /**
     * @template T of object
     * @param class-string<T> $attribute
     * @return T|null
     */
    private static function attribute(\ReflectionClass|\ReflectionProperty $target, string $attribute): ?object
    {
        $found = $target->getAttributes($attribute);
        return $found === [] ? null : $found[0]->newInstance();
    }

    private static function value(mixed $value): mixed
    {
        return $value instanceof \BackedEnum ? $value->value : $value;
    }
}
