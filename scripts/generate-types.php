#!/usr/bin/env php
<?php

/**
 * Writes php/src/Builder/Types.php from types/v1: one static method per type of the library.
 *
 * The builder hardcodes no type; this file is the library read once. `TypesTest` fails when it is out of date.
 * Usage: php scripts/generate-types.php
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

echo PortableSchema\Builder\TypesGenerator::source();
file_put_contents(__DIR__ . '/../php/src/Builder/Types.php', PortableSchema\Builder\TypesGenerator::source());
