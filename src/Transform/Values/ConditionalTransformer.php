<?php

declare(strict_types=1);

namespace Phetl\Transform\Values;

use Generator;
use InvalidArgumentException;

/**
 * Conditional transformation operations for ETL pipelines.
 *
 * Provides SQL-like conditional logic including CASE WHEN, COALESCE, NULL handling.
 */
final class ConditionalTransformer
{
    /**
     * Apply conditional logic: if condition is true, use then value, otherwise use else value.
     *
     * @param iterable<array<int|string, mixed>> $data The data to transform
     * @param string $field The field to evaluate
     * @param callable $condition Function that returns bool given field value
     * @param string $target Target field for the result
     * @param mixed|callable $thenValue Value or callback for true condition
     * @param mixed|callable $elseValue Value or callback for false condition
     * @return Generator<array<int|string, mixed>>
     * @throws InvalidArgumentException If field doesn't exist
     */
    public static function when(
        iterable $data,
        string $field,
        callable $condition,
        string $target,
        mixed $thenValue,
        mixed $elseValue
    ): Generator {
        $header = null;
        $fieldIndex = null;
        $targetIndex = null;

        foreach ($data as $index => $row) {
            if ($index === 0) {
                $header = $row;
                $fieldIndex = self::getFieldIndex($header, $field);

                // Add target field to header if not exists
                $targetIndex = array_search($target, $header, true);
                if ($targetIndex === false) {
                    $row[] = $target;
                    $targetIndex = count($row) - 1;
                }

                yield $row;
                continue;
            }

            $fieldValue = $row[$fieldIndex];
            $conditionResult = $condition($fieldValue);

            if ($conditionResult) {
                $row[$targetIndex] = is_callable($thenValue) ? $thenValue($row) : $thenValue;
            } else {
                $row[$targetIndex] = is_callable($elseValue) ? $elseValue($row) : $elseValue;
            }

            yield $row;
        }
    }

    /**
     * Return the first non-null value from a list of fields.
     *
     * Similar to SQL COALESCE - returns the first non-null value in order.
     * Note: Empty strings, 0, and false are NOT considered null.
     *
     * @param iterable<array<int|string, mixed>> $data The data to transform
     * @param string $target Target field for the result
     * @param array<string> $fields Fields to check in order
     * @return Generator<array<int|string, mixed>>
     * @throws InvalidArgumentException If any field doesn't exist or fields array is empty
     */
    public static function coalesce(
        iterable $data,
        string $target,
        array $fields
    ): Generator {
        if (empty($fields)) {
            throw new InvalidArgumentException('At least one field is required for coalesce');
        }

        $header = null;
        $fieldIndices = null;
        $targetIndex = null;

        foreach ($data as $index => $row) {
            if ($index === 0) {
                $header = $row;
                $fieldIndices = [];
                foreach ($fields as $field) {
                    $fieldIndices[] = self::getFieldIndex($header, $field);
                }

                // Add target field to header if not exists
                $targetIndex = array_search($target, $header, true);
                if ($targetIndex === false) {
                    $row[] = $target;
                    $targetIndex = count($row) - 1;
                }

                yield $row;
                continue;
            }

            $result = null;
            foreach ($fieldIndices as $fieldIndex) {
                if ($row[$fieldIndex] !== null) {
                    $result = $row[$fieldIndex];
                    break;
                }
            }

            $row[$targetIndex] = $result;
            yield $row;
        }
    }

    /**
     * Return null if the condition is true, otherwise return the original value.
     *
     * Useful for converting sentinel values to null (e.g., -999, 'N/A', etc.).
     *
     * @param iterable<array<int|string, mixed>> $data The data to transform
     * @param string $field The field to evaluate
     * @param string $target Target field for the result
     * @param callable $condition Function that returns bool given field value
     * @return Generator<array<int|string, mixed>>
     * @throws InvalidArgumentException If field doesn't exist
     */
    public static function nullIf(
        iterable $data,
        string $field,
        string $target,
        callable $condition
    ): Generator {
        $header = null;
        $fieldIndex = null;
        $targetIndex = null;

        foreach ($data as $index => $row) {
            if ($index === 0) {
                $header = $row;
                $fieldIndex = self::getFieldIndex($header, $field);

                // Add target field to header if not exists
                $targetIndex = array_search($target, $header, true);
                if ($targetIndex === false) {
                    $row[] = $target;
                    $targetIndex = count($row) - 1;
                }

                yield $row;
                continue;
            }

            $fieldValue = $row[$fieldIndex];
            $row[$targetIndex] = $condition($fieldValue) ? null : $fieldValue;

            yield $row;
        }
    }

    /**
     * Replace null values with a default value.
     *
     * Note: Only replaces actual null values, not empty strings or 0.
     *
     * @param iterable<array<int|string, mixed>> $data The data to transform
     * @param string $field The field to check
     * @param string $target Target field for the result
     * @param mixed|callable $default Default value or callback if null
     * @return Generator<array<int|string, mixed>>
     * @throws InvalidArgumentException If field doesn't exist
     */
    public static function ifNull(
        iterable $data,
        string $field,
        string $target,
        mixed $default
    ): Generator {
        $header = null;
        $fieldIndex = null;
        $targetIndex = null;

        foreach ($data as $index => $row) {
            if ($index === 0) {
                $header = $row;
                $fieldIndex = self::getFieldIndex($header, $field);

                // Add target field to header if not exists
                $targetIndex = array_search($target, $header, true);
                if ($targetIndex === false) {
                    $row[] = $target;
                    $targetIndex = count($row) - 1;
                }

                yield $row;
                continue;
            }

            $fieldValue = $row[$fieldIndex];

            if ($fieldValue === null) {
                $row[$targetIndex] = is_callable($default) ? $default($row) : $default;
            } else {
                $row[$targetIndex] = $fieldValue;
            }

            yield $row;
        }
    }

    /**
     * Evaluate multiple conditions in order (like SQL CASE WHEN).
     *
     * Evaluates each condition in order and returns the corresponding value
     * for the first true condition. If no conditions match, returns the default.
     *
     * @param iterable<array<int|string, mixed>> $data The data to transform
     * @param string $field The field to evaluate
     * @param string $target Target field for the result
     * @param array<array{callable, mixed|callable}> $conditions Array of [condition, value] pairs
     * @param mixed|callable $default Default value if no conditions match
     * @return Generator<array<int|string, mixed>>
     * @throws InvalidArgumentException If field doesn't exist
     */
    public static function case(
        iterable $data,
        string $field,
        string $target,
        array $conditions,
        mixed $default
    ): Generator {
        $header = null;
        $fieldIndex = null;
        $targetIndex = null;

        foreach ($data as $index => $row) {
            if ($index === 0) {
                $header = $row;
                $fieldIndex = self::getFieldIndex($header, $field);

                // Add target field to header if not exists
                $targetIndex = array_search($target, $header, true);
                if ($targetIndex === false) {
                    $row[] = $target;
                    $targetIndex = count($row) - 1;
                }

                yield $row;
                continue;
            }

            $fieldValue = $row[$fieldIndex];
            $result = is_callable($default) ? $default($row) : $default;

            foreach ($conditions as [$condition, $value]) {
                if ($condition($fieldValue)) {
                    $result = is_callable($value) ? $value($row) : $value;
                    break;
                }
            }

            $row[$targetIndex] = $result;
            yield $row;
        }
    }

    /**
     * Get field index from header, throwing exception if not found.
     *
     * @param array<int|string, mixed> $header The header row
     * @param string $field The field name
     * @return int|string The field index
     * @throws InvalidArgumentException If field doesn't exist
     */
    private static function getFieldIndex(array $header, string $field): int|string
    {
        $index = array_search($field, $header, true);
        if ($index === false) {
            throw new InvalidArgumentException("Field '{$field}' does not exist");
        }
        return $index;
    }
}
