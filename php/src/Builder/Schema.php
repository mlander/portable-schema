<?php

declare(strict_types=1);

namespace PortableSchema\Builder;

/**
 * A Portable Schema as a value: one keyword map that reads from and writes to the schema document 1:1.
 *
 * The class knows the dialect's vocabulary (`Keywords`) and nothing else: no type library, no host. A type from the
 * library is a reference, `Schema::of('portable:label')`; `Types` offers the library by name, generated from `types/`.
 *
 * Every method returns a new instance; a Schema never changes after it is made. Nested schemas are Schema instances
 * until `toDocument()` writes the whole tree in the JSON data model, which the Registry and the Validator take.
 *
 * Keyword methods: one per keyword, named after it without its prefix (`ref()` for `$ref`, `check()` for
 * `portable-check`, `ui()` for `x-portable-ui`), each taking the keyword's value. A few take arguments that build the
 * value (`typeBy()`, `check()`, `ui()`, `link()`, `message()`); `with()` takes any keyword by name.
 *
 * @method self id(string $id)
 * @method self schema(string $dialect)
 * @method self ref(string $ref)
 * @method self anchor(string $anchor)
 * @method self comment(string $comment)
 * @method self defs(array $schemas)
 * @method self prefixItems(array $schemas)
 * @method self items(Schema|array|bool $schema)
 * @method self contains(Schema|array|bool $schema)
 * @method self additionalProperties(Schema|array|bool $schema)
 * @method self patternProperties(array $schemas)
 * @method self dependentSchemas(array $schemas)
 * @method self propertyNames(Schema|array|bool $schema)
 * @method self if(Schema|array|bool $schema)
 * @method self then(Schema|array|bool $schema)
 * @method self else(Schema|array|bool $schema)
 * @method self allOf(array $schemas)
 * @method self anyOf(array $schemas)
 * @method self oneOf(array $schemas)
 * @method self not(Schema|array|bool $schema)
 * @method self unevaluatedItems(Schema|array|bool $schema)
 * @method self unevaluatedProperties(Schema|array|bool $schema)
 * @method self type(string|array $type)
 * @method self const(mixed $value)
 * @method self enum(array $values)
 * @method self multipleOf(int|float $divisor)
 * @method self maximum(int|float $limit)
 * @method self exclusiveMaximum(int|float $limit)
 * @method self minimum(int|float $limit)
 * @method self exclusiveMinimum(int|float $limit)
 * @method self maxLength(int $limit)
 * @method self minLength(int $limit)
 * @method self pattern(string $pattern)
 * @method self maxItems(int $limit)
 * @method self minItems(int $limit)
 * @method self uniqueItems(bool $unique)
 * @method self maxContains(int $limit)
 * @method self minContains(int $limit)
 * @method self maxProperties(int $limit)
 * @method self minProperties(int $limit)
 * @method self dependentRequired(array $dependencies)
 * @method self title(string|\Stringable $title)
 * @method self description(string|\Stringable $description)
 * @method self default(mixed $value)
 * @method self deprecated(bool $deprecated)
 * @method self readOnly(bool $readOnly)
 * @method self writeOnly(bool $writeOnly)
 * @method self examples(array $examples)
 * @method self format(string $format)
 * @method self contentEncoding(string $encoding)
 * @method self contentMediaType(string $mediaType)
 * @method self contentSchema(Schema|array|bool $schema)
 * @method self portableType(string $type)
 * @method self translatable(bool $translatable)
 * @method self provider(string $provider)
 * @method self links(array $links)
 * @method self messages(array $messages)
 * @method self unchecked(array $checks)
 */
final class Schema implements \JsonSerializable
{
    public const DIALECT = 'https://portable-schema.dev/v1/schema';

    /** @var array<string, mixed> keywords in the order they were given; schema-valued ones hold Schema instances */
    private array $keywords = [];

    /** A boolean schema (`true`, `false`), or null for an object schema. */
    private ?bool $boolean = null;

    /** Set by `required()` without names: the object this schema is placed in lists its key as required. */
    private bool $requiredInParent = false;

    private function __construct()
    {
    }

    // Starting points.

    /**
     * The empty schema: any value.
     */
    public static function any(): self
    {
        return new self();
    }

    /**
     * A reference to a schema by id: a type of the library (`portable:label`), a registry schema, or `#/$defs/name`.
     */
    public static function of(string $ref): self
    {
        return (new self())->with('$ref', $ref);
    }

    /**
     * A boolean schema: `true` allows everything, `false` nothing.
     */
    public static function boolean(bool $allows): self
    {
        $schema = new self();
        $schema->boolean = $allows;
        return $schema;
    }

    public static function string(): self
    {
        return (new self())->with('type', 'string');
    }

    public static function integer(): self
    {
        return (new self())->with('type', 'integer');
    }

    public static function number(): self
    {
        return (new self())->with('type', 'number');
    }

    public static function bool(): self
    {
        return (new self())->with('type', 'boolean');
    }

    public static function null(): self
    {
        return (new self())->with('type', 'null');
    }

    /**
     * An object with these properties. A property marked `required()` is listed in `required`.
     *
     * @param array<string, Schema|array|bool> $properties
     */
    public static function object(array $properties = []): self
    {
        $schema = (new self())->with('type', 'object');
        return $properties === [] ? $schema : $schema->properties($properties);
    }

    /**
     * A list whose items match one schema.
     */
    public static function list(self|array|bool $items): self
    {
        return (new self())->with('type', 'array')->with('items', $items);
    }

    /**
     * A map from any name to values of one schema.
     */
    public static function map(self|array|bool $values): self
    {
        return (new self())->with('type', 'object')->with('additionalProperties', $values);
    }

    /**
     * @param list<Schema|array|bool> $schemas
     */
    public static function anyOf(array $schemas): self
    {
        return (new self())->with('anyOf', $schemas);
    }

    /**
     * @param list<Schema|array|bool> $schemas
     */
    public static function oneOf(array $schemas): self
    {
        return (new self())->with('oneOf', $schemas);
    }

    /**
     * @param list<Schema|array|bool> $schemas
     */
    public static function allOf(array $schemas): self
    {
        return (new self())->with('allOf', $schemas);
    }

    /**
     * @param list<mixed> $values
     */
    public static function enum(array $values): self
    {
        return (new self())->with('enum', array_values($values));
    }

    public static function const(mixed $value): self
    {
        return (new self())->with('const', $value);
    }

    /**
     * A labeled list of allowed values: a `oneOf` of `const` + `title` branches, the shape of a catalog
     * (docs/decisions/0006-catalogs-as-labeled-oneof.md).
     *
     * @param array<int|string, string|\Stringable> $titles value to title
     */
    public static function options(array $titles): self
    {
        $branches = [];
        foreach ($titles as $value => $title) {
            $branches[] = self::const($value)->with('title', $title);
        }
        return self::oneOf($branches);
    }

    /**
     * A schema from a keyword map, e.g. `Schema::keywords(['type' => 'string', 'minLength' => 1])`.
     *
     * @param array<string, mixed> $keywords
     */
    public static function keywords(array $keywords): self
    {
        $schema = new self();
        foreach ($keywords as $keyword => $value) {
            $schema = $schema->with($keyword, $value);
        }
        return $schema;
    }

    /**
     * Reads a schema document. Every keyword is kept, known or not, so a document reads and writes back unchanged.
     */
    public static function from(\stdClass|array|bool|self $document): self
    {
        if ($document instanceof self) {
            return $document;
        }
        if (is_bool($document)) {
            return self::boolean($document);
        }
        $schema = new self();
        foreach ((array) $document as $keyword => $value) {
            $schema->keywords[$keyword] = self::read(Keywords::shape((string) $keyword), $value);
        }
        return $schema;
    }

    private static function read(?string $shape, mixed $value): mixed
    {
        return match ($shape) {
            Keywords::SCHEMA => is_bool($value) || $value instanceof \stdClass || is_array($value) ? self::from($value) : $value,
            Keywords::SCHEMA_MAP => $value instanceof \stdClass || is_array($value)
                ? array_map(fn ($schema) => self::read(Keywords::SCHEMA, $schema), (array) $value)
                : $value,
            Keywords::SCHEMA_LIST => is_array($value) ? array_map(fn ($schema) => self::read(Keywords::SCHEMA, $schema), $value) : $value,
            default => $value,
        };
    }

    // Keywords.

    /**
     * This schema with $keyword set to $value. Schema-valued keywords take a Schema, a keyword map or a boolean.
     *
     * @throws \InvalidArgumentException for a keyword outside the vocabulary or refused by the profile
     */
    public function with(string $keyword, mixed $value): self
    {
        if (in_array($keyword, Keywords::REFUSED, true)) {
            throw new \InvalidArgumentException("The profile refuses $keyword.");
        }
        $shape = Keywords::shape($keyword);
        if ($shape === null) {
            throw new \InvalidArgumentException("$keyword is not a Portable Schema keyword. Host annotations start with x-.");
        }
        $copy = clone $this;
        $copy->keywords[$keyword] = match ($shape) {
            Keywords::SCHEMA => self::from($value),
            Keywords::SCHEMA_MAP => array_map(self::from(...), $value),
            Keywords::SCHEMA_LIST => array_map(self::from(...), array_values($value)),
            default => $value,
        };
        return $copy;
    }

    /**
     * This schema without $keyword.
     */
    public function without(string $keyword): self
    {
        $copy = clone $this;
        unset($copy->keywords[$keyword]);
        return $copy;
    }

    /**
     * @param list<mixed> $arguments
     */
    public function __call(string $method, array $arguments): self
    {
        $keyword = Keywords::keyword($method);
        if ($keyword === null) {
            throw new \BadMethodCallException("Schema::$method() is not a keyword method.");
        }
        if (count($arguments) !== 1) {
            throw new \ArgumentCountError("Schema::$method() takes the keyword's value as its one argument.");
        }
        return $this->with($keyword, $arguments[0]);
    }

    /**
     * Without names: marks this schema as required where an object places it. With names: the `required` keyword.
     */
    public function required(string ...$names): self
    {
        if ($names !== []) {
            return $this->with('required', array_values($names));
        }
        $copy = clone $this;
        $copy->requiredInParent = true;
        return $copy;
    }

    /**
     * Adds properties, keeping the ones already declared, and lists the ones marked `required()`.
     *
     * @param array<string, Schema|array|bool> $properties
     */
    public function properties(array $properties): self
    {
        $all = $this->keywords['properties'] ?? [];
        $required = $this->keywords['required'] ?? [];
        foreach ($properties as $name => $property) {
            $property = self::from($property);
            $all[$name] = $property;
            if ($property->requiredInParent && !in_array($name, $required, true)) {
                $required[] = $name;
            }
        }
        $copy = $this->with('properties', $all);
        return $required === [] ? $copy : $copy->with('required', $required);
    }

    public function property(string $name, self|array|bool $schema): self
    {
        return $this->properties([$name => $schema]);
    }

    /**
     * A named schema under `$defs`, referenced as `Schema::of('#/$defs/name')`.
     */
    public function def(string $name, self|array|bool $schema): self
    {
        return $this->with('$defs', ($this->keywords['$defs'] ?? []) + [$name => self::from($schema)]);
    }

    /**
     * A named check (`portable-check`), added to the ones already declared.
     *
     * @param array<string, mixed> $options
     */
    public function check(string $name, array $options = []): self
    {
        $checks = $this->keywords['portable-check'] ?? [];
        $checks[$name] = (object) $options;
        return $this->with('portable-check', $checks);
    }

    /**
     * The schema of this value chosen by other values (`portable-typeBy`).
     *
     * @param array<string, string> $source template variables to Relative JSON Pointers; `Location` spells them
     */
    public function typeBy(string $href, array $source, ?string $fallback = null): self
    {
        $value = ['href' => $href, 'source' => (object) $source];
        if ($fallback !== null) {
            $value['fallback'] = $fallback;
        }
        return $this->with('portable-typeBy', $value);
    }

    /**
     * A computed narrowing of this schema by a provider (`portable-refine`).
     *
     * @param array<string, string> $source
     */
    public function refine(string $provider, array $source): self
    {
        return $this->with('portable-refine', ['provider' => $provider, 'source' => (object) $source]);
    }

    /**
     * A rule against another value (`portable-compare`): this value $op the value at $pointer.
     */
    public function compare(string $op, string $pointer): self
    {
        return $this->with('portable-compare', ['op' => $op, 'pointer' => $pointer]);
    }

    /**
     * UI hints (`x-portable-ui`), merged with the ones already given.
     *
     * @param array<string, mixed>|null $options
     * @param list<string>|null $fallback
     */
    public function ui(?string $widget = null, ?array $options = null, ?array $fallback = null, ?string $group = null, int|float|null $weight = null): self
    {
        $given = array_filter(compact('widget', 'options', 'fallback', 'group', 'weight'), fn ($v) => $v !== null);
        $ui = array_merge((array) ($this->keywords['x-portable-ui'] ?? []), $given);
        return $this->with('x-portable-ui', $ui);
    }

    /**
     * A data source for forms (`x-portable-links`), added to the ones already given.
     *
     * @param array<string, string>|null $source
     */
    public function link(string $rel, string $href, ?array $source = null): self
    {
        $link = ['rel' => $rel, 'href' => $href];
        if ($source !== null) {
            $link['source'] = (object) $source;
        }
        return $this->with('x-portable-links', [...($this->keywords['x-portable-links'] ?? []), $link]);
    }

    /**
     * A custom message source text for an error code (`x-portable-messages`).
     */
    public function message(string $code, string|\Stringable $text): self
    {
        return $this->with('x-portable-messages', ($this->keywords['x-portable-messages'] ?? []) + [$code => $text]);
    }

    /**
     * A host annotation: an `x-` keyword the validator ignores, e.g. what a form layer records for itself.
     */
    public function annotation(string $name, mixed $value): self
    {
        if (!str_starts_with($name, 'x-')) {
            throw new \InvalidArgumentException("An annotation keyword starts with x-, $name does not.");
        }
        return $this->with($name, $value);
    }

    /**
     * A conditional on this object: when every named property equals its value, $then applies, else $else.
     * Written as `if`/`then`/`else` in `allOf`, the one spelling every validator reads.
     *
     * @param array<string, mixed> $equals
     */
    public function when(array $equals, self|array|bool $then, self|array|bool|null $else = null): self
    {
        $if = self::object(array_map(self::const(...), $equals))->without('type')->required(...array_keys($equals));
        $conditional = self::any()->with('if', $if)->with('then', $then);
        if ($else !== null) {
            $conditional = $conditional->with('else', $else);
        }
        return $this->with('allOf', [...($this->keywords['allOf'] ?? []), $conditional]);
    }

    // Reading.

    public function has(string $keyword): bool
    {
        return array_key_exists($keyword, $this->keywords);
    }

    /**
     * The keyword's value as held: a Schema, a list or map of them, or the JSON value.
     */
    public function get(string $keyword): mixed
    {
        return $this->keywords[$keyword] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->keywords;
    }

    public function isBoolean(): bool
    {
        return $this->boolean !== null;
    }

    public function isRequired(): bool
    {
        return $this->requiredInParent;
    }

    // Writing.

    /**
     * The schema in the JSON data model: objects as \stdClass, schemas as nested documents, translatable strings
     * rendered. What `Registry::add()` and `Validator` take.
     */
    public function toDocument(): \stdClass|bool
    {
        if ($this->boolean !== null) {
            return $this->boolean;
        }
        $document = new \stdClass();
        foreach ($this->keywords as $keyword => $value) {
            $written = self::write($value);
            // A map of schemas is an object even when its names look like indexes.
            if (Keywords::shape($keyword) === Keywords::SCHEMA_MAP && is_array($written)) {
                $written = (object) $written;
            }
            $document->{$keyword} = $written;
        }
        return $document;
    }

    /**
     * The root document: `$schema` names the dialect and comes first, then `$id`, `title` and `description`, the
     * head every schema file starts with; the rest keeps its authoring order.
     */
    public function document(): \stdClass
    {
        $document = $this->toDocument();
        if (!$document instanceof \stdClass) {
            throw new \LogicException('A boolean schema is not a document.');
        }
        $ordered = new \stdClass();
        $ordered->{'$schema'} = $document->{'$schema'} ?? self::DIALECT;
        foreach (['$id', 'title', 'description'] as $head) {
            if (isset($document->{$head})) {
                $ordered->{$head} = $document->{$head};
            }
        }
        foreach ((array) $document as $keyword => $value) {
            $ordered->{$keyword} ??= $value;
        }
        return $ordered;
    }

    private static function write(mixed $value): mixed
    {
        if ($value instanceof self) {
            return $value->toDocument();
        }
        if ($value instanceof \Stringable) {
            return (string) $value;
        }
        if ($value instanceof \stdClass) {
            $copy = new \stdClass();
            foreach ((array) $value as $name => $item) {
                $copy->{$name} = self::write($item);
            }
            return $copy;
        }
        if (is_array($value)) {
            $written = array_map(self::write(...), $value);
            return array_is_list($value) ? $written : (object) $written;
        }
        return $value;
    }

    public function jsonSerialize(): \stdClass|bool
    {
        return $this->toDocument();
    }

    /**
     * The document as JSON, for fixtures and diffs.
     */
    public function toJson(): string
    {
        return json_encode($this->toDocument(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
