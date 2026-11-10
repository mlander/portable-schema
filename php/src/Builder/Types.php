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
    /**
     * Binary: `portable:binary`. Binary data, base64-encoded.
     */
    public static function binary(): Schema
    {
        return Schema::of('portable:binary');
    }

    /**
     * Boolean: `portable:boolean`.
     */
    public static function boolean(): Schema
    {
        return Schema::of('portable:boolean');
    }

    /**
     * Color: `portable:color`. A hex color: #rgb or #rrggbb; the # is optional, as in Drupal's color_hex.
     */
    public static function color(): Schema
    {
        return Schema::of('portable:color');
    }

    /**
     * Date: `portable:date`. A calendar date, without time or time zone.
     */
    public static function date(): Schema
    {
        return Schema::of('portable:date');
    }

    /**
     * Date and time: `portable:date-time`. A point in time with a time zone offset (RFC 3339).
     */
    public static function dateTime(): Schema
    {
        return Schema::of('portable:date-time');
    }

    /**
     * Decimal: `portable:decimal`. A fixed-point decimal number as a string, so no precision is lost.
     */
    public static function decimal(): Schema
    {
        return Schema::of('portable:decimal');
    }

    /**
     * Duration: `portable:duration`. An ISO 8601 duration, e.g. PT30M.
     */
    public static function duration(): Schema
    {
        return Schema::of('portable:duration');
    }

    /**
     * Email address: `portable:email`.
     */
    public static function email(): Schema
    {
        return Schema::of('portable:email');
    }

    /**
     * HTML: `portable:html`. Formatted text as HTML. Filtering is up to the host.
     */
    public static function html(): Schema
    {
        return Schema::of('portable:html');
    }

    /**
     * Identifier: `portable:identifier`. A machine name: lowercase letters, digits and underscores.
     */
    public static function identifier(): Schema
    {
        return Schema::of('portable:identifier');
    }

    /**
     * Image: `portable:image`. An image to display. Open: use sites may add keys or require alt.
     */
    public static function image(): Schema
    {
        return Schema::of('portable:image');
    }

    /**
     * Integer: `portable:integer`. A whole number within the I-JSON range, so PHP and JS agree.
     */
    public static function integer(): Schema
    {
        return Schema::of('portable:integer');
    }

    /**
     * IRI: `portable:iri`. An absolute IRI: a URI that may contain non-ASCII characters, as people enter links.
     */
    public static function iri(): Schema
    {
        return Schema::of('portable:iri');
    }

    /**
     * Label: `portable:label`. Short, single-line, translatable text.
     */
    public static function label(): Schema
    {
        return Schema::of('portable:label');
    }

    /**
     * Language code: `portable:langcode`. A well-formed BCP 47 language tag (RFC 5646), e.g. en, pt-br, zh-hans, x-default.
     */
    public static function langcode(): Schema
    {
        return Schema::of('portable:langcode');
    }

    /**
     * Link: `portable:link`. A link: target and text. Open: use sites may add keys.
     */
    public static function link(): Schema
    {
        return Schema::of('portable:link');
    }

    /**
     * Markdown: `portable:markdown`. Formatted text as CommonMark.
     */
    public static function markdown(): Schema
    {
        return Schema::of('portable:markdown');
    }

    /**
     * Number: `portable:number`. A floating-point number.
     */
    public static function number(): Schema
    {
        return Schema::of('portable:number');
    }

    /**
     * Secret: `portable:secret`. A value that is accepted but never returned, e.g. a password or API key.
     */
    public static function secret(): Schema
    {
        return Schema::of('portable:secret');
    }

    /**
     * String: `portable:string`. Any text.
     */
    public static function string(): Schema
    {
        return Schema::of('portable:string');
    }

    /**
     * Text: `portable:text`. Multi-line, translatable plain text.
     */
    public static function text(): Schema
    {
        return Schema::of('portable:text');
    }

    /**
     * Time span: `portable:timespan`. A length of time in seconds.
     */
    public static function timespan(): Schema
    {
        return Schema::of('portable:timespan');
    }

    /**
     * Timestamp: `portable:timestamp`. A point in time as Unix seconds.
     */
    public static function timestamp(): Schema
    {
        return Schema::of('portable:timestamp');
    }

    /**
     * URI: `portable:uri`. An absolute URI.
     */
    public static function uri(): Schema
    {
        return Schema::of('portable:uri');
    }

    /**
     * URI reference: `portable:uri-reference`. A URI or a relative reference, e.g. \/node\/1.
     */
    public static function uriReference(): Schema
    {
        return Schema::of('portable:uri-reference');
    }

    /**
     * UUID: `portable:uuid`.
     */
    public static function uuid(): Schema
    {
        return Schema::of('portable:uuid');
    }

    /**
     * Weight: `portable:weight`. A sort order: lower comes first.
     */
    public static function weight(): Schema
    {
        return Schema::of('portable:weight');
    }
}
