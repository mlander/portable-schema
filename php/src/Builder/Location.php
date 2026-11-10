<?php

declare(strict_types=1);

namespace PortableSchema\Builder;

/**
 * Spells the Relative JSON Pointers that `portable-typeBy`, `portable-refine`, `portable-compare` and
 * `x-portable-links` take in `source` and `pointer` (docs/decisions/0002-relative-json-pointer.md).
 *
 * A pointer is relative to the value the schema applies to, so it needs no knowledge of the tree: a sibling is
 * always `1/<key>`, whatever the depth.
 */
final class Location
{
    private const PATTERN = '/^(0|[1-9][0-9]*)(#|(\/([^~\/]|~[01])*)*)$/';

    /**
     * A key of the value this schema applies to: `0/<key>`.
     */
    public static function own(string $key): string
    {
        return '0' . self::path($key);
    }

    /**
     * A key beside this value, in the object that holds it: `1/<key>`. Config schema's `[%parent.key]`.
     */
    public static function sibling(string $key): string
    {
        return '1' . self::path($key);
    }

    /**
     * The name this value is stored under in its parent: `0#`. Config schema's `[%key]`.
     */
    public static function name(): string
    {
        return '0#';
    }

    /**
     * $levels up from this value, then down the given keys: `2/x/y`.
     */
    public static function up(int $levels, string ...$keys): string
    {
        if ($levels < 0) {
            throw new \InvalidArgumentException('Levels up cannot be negative.');
        }
        return $levels . implode('', array_map(self::path(...), $keys));
    }

    /**
     * Checks a pointer written by hand against the allowed form.
     */
    public static function pointer(string $pointer): string
    {
        if (!preg_match(self::PATTERN, $pointer)) {
            throw new \InvalidArgumentException("$pointer is not a Relative JSON Pointer in the allowed form.");
        }
        return $pointer;
    }

    private static function path(string $key): string
    {
        return '/' . str_replace(['~', '/'], ['~0', '~1'], $key);
    }
}
