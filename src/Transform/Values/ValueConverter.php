<?php

declare(strict_types=1);

namespace Phetl\Transform\Values;

use Closure;
use Generator;
use InvalidArgumentException;

/**
 * Value conversion transformations for transforming cell values.
 */
class ValueConverter
{
    /**
     * Convert values in a specific column using a function.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @param string $field Field name to convert
     * @param callable|string $converter Callable or function name
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function convert(iterable $data, string $field, callable|string $converter): Generator
    {
        $headerProcessed = false;
        /** @var int|string|null $fieldIndex */
        $fieldIndex = null;

        foreach ($data as $row) {
            if (!$headerProcessed) {
                // Find field index
                $fieldIndex = array_search($field, $row, true);
                if ($fieldIndex === false) {
                    throw new InvalidArgumentException("Field '$field' not found in header");
                }
                yield $row;
                $headerProcessed = true;
                continue;
            }

            // Convert the value
            if ($fieldIndex !== null && isset($row[$fieldIndex])) {
                if (is_string($converter) && is_callable($converter)) {
                    // Use string as function name (e.g., 'strtoupper', 'intval')
                    $row[$fieldIndex] = $converter($row[$fieldIndex]);
                } else {
                    /** @var callable $converter */
                    $row[$fieldIndex] = $converter($row[$fieldIndex]);
                }
            }

            yield $row;
        }
    }

    /**
     * Convert values in multiple columns.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @param array<string, callable|string> $conversions Field => Converter mapping
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function convertMultiple(iterable $data, array $conversions): Generator
    {
        $headerProcessed = false;
        /** @var array<string, int|string> $fieldIndices */
        $fieldIndices = [];

        foreach ($data as $row) {
            if (!$headerProcessed) {
                // Build field index mapping
                foreach ($conversions as $field => $converter) {
                    $index = array_search($field, $row, true);
                    if ($index !== false) {
                        $fieldIndices[$field] = $index;
                    }
                }
                yield $row;
                $headerProcessed = true;
                continue;
            }

            // Convert values
            foreach ($fieldIndices as $field => $index) {
                if (isset($row[$index])) {
                    $converter = $conversions[$field];
                    if (is_string($converter) && is_callable($converter)) {
                        $row[$index] = $converter($row[$index]);
                    } else {
                        /** @var callable $converter */
                        $row[$index] = $converter($row[$index]);
                    }
                }
            }

            yield $row;
        }
    }
}
