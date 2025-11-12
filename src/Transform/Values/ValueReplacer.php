<?php

declare(strict_types=1);

namespace Phetl\Transform\Values;

use Generator;
use InvalidArgumentException;

/**
 * Value replacement transformations.
 */
class ValueReplacer
{
    /**
     * Replace a specific value in a field with another value.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @param string $field Field name
     * @param mixed $oldValue Value to replace
     * @param mixed $newValue Replacement value
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function replace(
        iterable $data,
        string $field,
        mixed $oldValue,
        mixed $newValue
    ): Generator {
        $headerProcessed = false;
        /** @var int|string|null $fieldIndex */
        $fieldIndex = null;

        foreach ($data as $row) {
            if (!$headerProcessed) {
                $fieldIndex = array_search($field, $row, true);
                if ($fieldIndex === false) {
                    throw new InvalidArgumentException("Field '$field' not found in header");
                }
                yield $row;
                $headerProcessed = true;
                continue;
            }

            // Replace value if it matches
            if ($fieldIndex !== null && isset($row[$fieldIndex]) && $row[$fieldIndex] === $oldValue) {
                $row[$fieldIndex] = $newValue;
            }

            yield $row;
        }
    }

    /**
     * Replace multiple values in a field using a mapping.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @param string $field Field name
     * @param array<mixed, mixed> $mapping Old value => New value
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function replaceMap(iterable $data, string $field, array $mapping): Generator
    {
        $headerProcessed = false;
        /** @var int|string|null $fieldIndex */
        $fieldIndex = null;

        foreach ($data as $row) {
            if (!$headerProcessed) {
                $fieldIndex = array_search($field, $row, true);
                if ($fieldIndex === false) {
                    throw new InvalidArgumentException("Field '$field' not found in header");
                }
                yield $row;
                $headerProcessed = true;
                continue;
            }

            // Replace value if found in mapping
            if ($fieldIndex !== null && isset($row[$fieldIndex])) {
                $currentValue = $row[$fieldIndex];
                /** @var int|string $currentValue */
                if (array_key_exists($currentValue, $mapping)) {
                    $row[$fieldIndex] = $mapping[$currentValue];
                }
            }

            yield $row;
        }
    }

    /**
     * Replace all occurrences across all fields.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @param mixed $oldValue Value to replace
     * @param mixed $newValue Replacement value
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function replaceAll(iterable $data, mixed $oldValue, mixed $newValue): Generator
    {
        $headerProcessed = false;

        foreach ($data as $row) {
            if (!$headerProcessed) {
                yield $row;
                $headerProcessed = true;
                continue;
            }

            // Replace in all fields
            foreach ($row as $index => $value) {
                if ($value === $oldValue) {
                    $row[$index] = $newValue;
                }
            }

            yield $row;
        }
    }
}
