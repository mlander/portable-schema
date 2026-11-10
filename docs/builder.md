# Builder

Status: proposal, with a PHP prototype under [`php/src/Builder/`](../php/src/Builder). Nothing here is stable.

The builder is the PHP authoring API for a Portable Schema. It writes the schema document; it is one way to write it. A hand-written schema, a generated one and a built one are equal: the document is the contract, and every validator, form generator and exporter reads the document.

## What it is

- **A value object that maps 1:1 to the document.** `Schema` holds one keyword map. Every keyword method sets one keyword; `toDocument()` writes the map in the JSON data model and `Schema::from()` reads a document back. Reading and writing lose nothing, known keyword or not: every schema under `types/` and `fixtures/examples` reads into a `Schema` and writes back byte for byte (`SchemaTest`).
- **It knows the vocabulary, not the types.** The keywords it offers are the ones the dialect meta-schema includes, minus what the profile refuses, plus the Portable Schema vocabulary. `Keywords::TABLE` lists them and `KeywordsTest` derives the same list from `spec/v1/schema.json`, the 2020-12 meta-schemas and `spec/v1/vocab.json`, so the builder cannot know a keyword the validator does not. A type of the library is a reference, `Schema::of('portable:label')`; `Types::label()` says the same and is generated from `types/v1` (`scripts/generate-types.php`, held current by `TypesTest`). Integrations generate the same kind of class for their own types and catalogs.
- **Immutable.** Every method returns a new instance, so a schema can be shared and narrowed without copies.
- **Host-neutral.** No Drupal dependency. Labels take `string|\Stringable`, so Drupal's `t()` renders at write time. A host keeps its own facts in `x-` annotations, `->annotation('x-surface', [...])`, which the profile reads as annotations and the validator ignores.

## Two spellings, one document

Fluent calls:

```php
$settings = Schema::object([
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
        Schema::integer()->minimum(1), // managed media ID
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

$registry->add($settings->document());
```

A class with attributes:

```php
#[Document('mymodule:display_settings', title: 'Display settings')]
final class DisplaySettings
{
    #[Of('portable:label')]
    #[Schema(title: 'Heading', description: 'Text shown above the listing.')]
    public string $heading = 'Latest articles';

    #[Of('portable:integer')]
    #[Schema(title: 'Items per page', minimum: 1, maximum: 100)]
    public int $items_per_page = 20;

    #[Schema(title: 'Show author')]
    public bool $show_author = true;

    #[Schema(
        anyOf: [['type' => 'string', 'format' => 'uri'], ['type' => 'integer', 'minimum' => 1]],
        title: 'Logo',
        description: 'A logo URI or the ID of a managed media item.',
    )]
    public mixed $logo;

    #[Schema(title: 'Featured items', items: FeaturedItem::class)]
    public array $featured_items = [];
}

$registry->add(ClassReader::read(DisplaySettings::class)->document());
```

Both produce [`fixtures/builder/display-settings.json`](../fixtures/builder/display-settings.json). The attribute spelling is a reader over the fluent one: `ClassReader` calls the same methods, so a class can never say something the calls cannot.

### How the methods are named

| Keyword | Method |
|---|---|
| A standard keyword | Its own name: `minLength(1)`, `default(20)`, `if(...)`, `not(...)` |
| `$id`, `$ref`, `$defs`, `$comment`, `$anchor` | Without `$`: `id()`, `ref()`, `defs()`, `comment()`, `anchor()` |
| `portable-check`, `portable-typeBy`, `portable-refine`, `portable-compare` | `check(name, options)`, `typeBy(href, source, fallback)`, `refine(provider, source)`, `compare(op, pointer)` |
| `x-portable-ui`, `x-portable-links`, `x-portable-messages`, `x-portable-type`, ... | `ui(...)`, `link(rel, href, source)`, `message(code, text)`, `portableType()`, `translatable()`, `provider()`, `unchecked()` |
| Any `x-` keyword | `annotation(name, value)` |
| Any keyword by name | `with(keyword, value)`; `without(keyword)` |

Starting points: `Schema::of($ref)`, `Schema::string()`, `integer()`, `number()`, `bool()`, `null()`, `object([...])`, `list($items)`, `map($values)`, `anyOf()`, `oneOf()`, `allOf()`, `enum()`, `const()`, `options([value => title])` (a labeled catalog), `any()`, `boolean(false)`, `keywords([...])`, `from($document)`.

Three conveniences go beyond one keyword each, and each writes standard keywords only:

- `->required()` without names marks a property; the object that places it lists it in `required`. With names it is the keyword.
- `->when(['public' => true], then: ..., else: ...)` on an object appends an `if`/`then`/`else` to `allOf`, with `if` spelled as `properties` + `required`.
- `Location::sibling('plugin')`, `own('id')`, `name()`, `up(2, 'x')` spell the Relative JSON Pointers that `source` and `pointer` take; `Location::pointer()` checks one written by hand.

### What a class says

| PHP | Schema |
|---|---|
| `public string $x`, `int`, `float`, `bool`, `array` | `type`: `string`, `integer`, `number`, `boolean`, `array` |
| `#[Of('portable:label')]` | `$ref`; the PHP type then only says required and nullable |
| `?T`, or `T\|null` | `null` added to `type`; with `#[Of]`, `anyOf` of the reference and `null` |
| A backed enum | `enum` of its values |
| A class | Read the same way: `$ref` to its `#[Document]` id, else written inline |
| `mixed` | Any value; say the rest with `#[Schema]` |
| An initializer | `default` |
| No default, and no null allowed | Listed in `required`; `#[Required]` lists a property that has a default |
| `#[Schema(...)]` | Any keywords by method name; a schema-valued one takes a keyword map or a class name |
| `#[Check(name, options)]`, repeatable | `portable-check` |
| `#[Ui(widget:, options:, fallback:, group:, weight:)]` | `x-portable-ui` |
| `#[Document(id, title, description)]` on the class | `$id`, `title`, `description` of the root |
| A class with public properties | `type: object`, `properties` in declaration order, `unevaluatedProperties: false` (a class is a concrete type; `#[Schema(unevaluatedProperties: true)]` opens it) |
| A class without public properties | One value: its `#[Of]` and `#[Schema]`, nothing added |
| `#[Of(TheClass::class)]`, or a property typed with a class that has a `#[Document]` id | `$ref` to that id |

## Types: the library, the integration, the modules

`Types` is the library and only the library. Nothing extends the class; a type outside the library is a reference to a schema in the registry, and the registry is open.

| Tier | Ids | Written by | Spelled as |
|---|---|---|---|
| The library | `portable:label` | This repository, `types/v1` | `Types::label()`, generated |
| The integration | `drupal:field_item.image`, `drupal:catalog/plugins/block` | The Drupal integration, from Typed Data, config schema and plugin discovery | `Schema::of('drupal:field_item.image')`; the integration can generate its own class the way `Types` is generated |
| A module | `drupal:surface_examples/phone` | The module, with the builder, or as a JSON file it ships | `Schema::of(PhoneNumber::class)`, or the class as a property type |

A module's types are classes. A class without public properties is a type for one value; a class with properties is a type for an object. Surface API calls the first a shape and the second a structure, and both are the same thing here: a schema with a `#[Document]` id, read by `ClassReader` and added to the registry.

```php
#[Document('drupal:surface_examples/phone', title: 'Phone')]
#[Of('portable:string')]
#[Schema(pattern: '^\+?[0-9 ]{7,20}$', examples: ['+1 555 0100'])]
final class PhoneNumber
{
}

#[Document('drupal:surface_examples/contact', title: 'Contact')]
final class Contact
{
    #[Of('portable:label')]
    public string $name;

    public ?PhoneNumber $phone = null;      // anyOf: the reference, or null

    #[Of(PhoneNumber::class)]
    public string $fax;                     // $ref: drupal:surface_examples/phone
}
```

A property typed with such a class, `#[Of(TheClass::class)]` or `Schema::of(TheClass::class)` is a `$ref` to its id. A class without an id is written inline. The module registers nothing by hand: the integration discovers the classes (as Surface API discovers `#[Resource]` today), reads them, and adds the documents to the registry under the module's path, cached like a site schema dump.

Another module extends a module's type the way it extends anything in Drupal: through the alter and extend contracts of the host, which compose a new document from the owner's and the contributor's calls. The builder itself has no extension point, because a type is a document and a document has no author.

**Ids:** a schema id is a URI, so its namespace is a scheme, and a scheme allows letters, digits, `+`, `-` and `.` but not `_`. `surface_examples:phone` is refused by the registry; `spec/registry.md` allows `_` in a namespace, which the URI rule contradicts. Module machine names therefore go in the path, under the integration's namespace: `drupal:surface_examples/phone`. To raise with fago.

## Rules

1. **The document is the contract.** `toDocument()` is the builder's whole output. Nothing a builder can say is missing from the document, and nothing the document can say needs the builder.
2. **Vocabulary from the spec files, never by hand.** The keyword table, the `@method` lines and `Types` are each held to a spec file by a test. When the spec adds a keyword or a type, the builder fails until it follows.
3. **Unknown keywords are refused at authoring time.** `with('label', ...)` throws, as the next JSON Schema release makes an unknown keyword an error. `x-` keywords pass as annotations. `Schema::from()` keeps everything, so a document never loses a keyword by being read.
4. **Required means present, and a default is not presence.** A property with a default is not required. This follows `spec/normalization.md` and the examples under `fixtures/examples/drupal/agent-tool`.
5. **Key order is authoring order.** `document()` puts `$schema`, `$id`, `title` and `description` first, the head every schema file starts with, and changes nothing else. Comparisons of built documents sort keys; the Registry stores JS order anyway.
6. **A labeled option list is `oneOf` + `const` + `title`** ([decision 0006](decisions/0006-catalogs-as-labeled-oneof.md)). `Schema::options()` writes that shape. PHP turns the array key `'0'` into `0`, so a value that is a numeric string is written with `Schema::const('0')->title(...)` in a `oneOf`.

## What it does not do

| Left out | Where it lives |
|---|---|
| Composition from several authors: ownership, namespaces for an added key, "a fixed list only gains values" | The host. The builder merges nothing on its own; `properties()` and `check()` add to what is declared and that is all. Data Surfaces keeps its alter and extend contracts. |
| Validation of the built schema against the profile | `Registry::add()` and `new Validator()`, as for any document |
| Rendering: forms, widgets | The host's adapter walks the document, or `Validator::subschemaAt()` |
| Typed Data, config schema | An integration generates documents from them; the builder is not involved |
| A JS builder | Later, from the same fixtures |

## For Data Surfaces

What the Surface API builder says today and how the document says it, so the retrofit is a mapping, not a redesign:

| Surface today | Portable Schema |
|---|---|
| `add('key', 'string', t('Label'))`, `setDescription()`, `default:` | `title`, `description`, `default` |
| `setRequired(TRUE)` | `required()` |
| `addConstraint('Length', ...)`, `Range`, `Count`, `Regex`, `NotBlank`, `Uuid`, `Email` | Standard keywords through the same table `ConstraintKeywords` holds today; the rest `check('drupal:<Name>', options)` |
| A choice list, `LabeledChoice` | `options()` or `enum()`; a list the site decides, a `$ref` to a catalog |
| A reference to an entity, `SurfaceValidReference` | `check('drupal:ValidReference')`, and `link('lookup', href, source)` for the lookup |
| `lock()`, `hold()`, a computed key | `readOnly(true)` |
| `omit()` | The key left out of the document |
| `setSetting('widget', ...)`, `describe(group:, weight:)` | `ui(widget:, group:, weight:, options:)`; placeholder, prefix, suffix and help under `options` |
| `narrow('key')->when(Condition::equals(...))` | `when()`, or `if`/`then` by hand |
| `attachVariants('settings', by: 'plugin', ...)` | `typeBy('drupal:block.settings.{plugin}', ['plugin' => Location::sibling('plugin')], fallback)` where each variant is a registry schema; `oneOf` of inline objects where they are not |
| `#[RefinesInput]`, a runtime refiner | `refine('surface:<provider>', source)`, answered by a provider the site registers |
| A shape (`PhoneNumber::define()`), a structure (`#[Structure]`) | A class with a `#[Document]` id, without or with properties; see [Types](#types-the-library-the-integration-the-modules) |
| `attach()`, `attachList()`, `mountStructure()`, ports, mixins | `of('<id>')`, `list(of(...))`, `properties()`; the wiring of ports and the owner labels stay in Surface |
| `x-surface` facts (`dependsOn`, `refined`, `live`, `fixed`, `rules`, `within`) | `annotation('x-surface', [...])` while they exist; most say what `portable-typeBy`, `portable-refine` and `if`/`then` already say in the document |
| The schema reader, `SchemaReader::structure()` | `Schema::from()`, then a walk over `Schema` instead of over arrays |
| Form API generation from the `SurfaceInterface` | An adapter that walks the `Schema` tree, or asks `subschemaAt()` per element |

Surface's `#[Input]`, `#[Constraint]`, `#[Widget]`, `#[Describe]` and `#[Rule]` attributes become `#[Of]`, `#[Schema]`, `#[Check]` and `#[Ui]`, or stay as Surface's own attributes that its reader turns into the same calls. Operations, access, storage, staging and the pipeline do not change: they take a document instead of a `SurfaceInterface`.

## Open questions

1. **Does a builder belong in this repository?** Decided 2026-11-10: yes. Spec, builder, validators and fixtures live together, with Surface API as a host. It stays in `php/src/Builder`.
2. **How far does `Keywords` go?** Today it is a table held to the meta-schemas by a test. The next step is to generate it, and the `@method` lines, from the meta-schemas, so a new vocabulary version changes the builder with no hand edit.
3. **`required` with a default.** Rule 4 follows the fixtures. Drupal's config schema requires every key and fills defaults on save, so a config schema converter may need `#[Required]` on every property, or an option on `ClassReader`.
4. **Nullable references.** `?T` with `#[Of]` writes `anyOf: [{$ref}, {type: null}]`. A `type: [..., "null"]` beside the `$ref` would be shorter, but it needs the referenced type's JSON type, which only the registry knows.
5. **Where do composition rules go?** Composition is two or more authors writing one document: a module adding a key to another module's resource, a contributor adding values to a fixed list, two alters touching one keyword. The rules decide what each may do: an added key lands under its author's name, a fixed list only gains values, a key that takes any value is never given a list by a contributor, one keyword has one owner and a second writer is refused. Decided 2026-11-10: they are part of Drupal's alter and extend contracts and stay in Surface API, which already has them (`extendChoices()`, `setThirdPartyDefinition()`, `mountStructure()`, `withdraw()`). The builder stays a value: `properties()` and `check()` add, nothing is refused and no owner is recorded. If a second host needs the same rules, a `Composer` beside the builder can take owner labels and apply them to `Schema` values with no Drupal dependency.
6. **A JS builder.** The fixtures under `fixtures/builder` are the contract for one. Not started.
7. **Namespaces with `_`.** `spec/registry.md` allows `[a-z][a-z0-9_]*`, the registry refuses `surface_examples:phone` because a URI scheme has no `_`. Either the spec drops `_`, or module names go in the path under `drupal:`. The prototype does the second.
8. **Canonical key order.** Rule 5 keeps authoring order. If diffs of generated schemas need stability, the order could follow `Keywords::TABLE`, which follows the meta-schemas.

## Plan

| Step | Done when |
|---|---|
| 1. Prototype in this branch | `Schema`, `Keywords`, `Types`, `Location`, `ClassReader` and the attributes pass their tests; every repository schema round-trips. Done. |
| 2. Decisions | Questions 1 and 5 decided; question 2 open; a decision record follows |
| 3. Generated vocabulary | `Keywords` and the `@method` lines generated from the meta-schemas, like `Types` from `types/` |
| 4. Builder fixtures | More documents under `fixtures/builder`: `typeBy` with a fallback, a labeled catalog, a conditional, a check, a nullable reference, a nested class by `$ref` |
| 5. Surface API retrofit, in Surface | The contract emitter writes through `Schema`; the schema reader reads through `Schema::from()`; the Form API adapter walks the document. Each step keeps Surface's 42 contract baselines byte-identical or explains the change |
| 6. JS builder | From the same fixtures |
