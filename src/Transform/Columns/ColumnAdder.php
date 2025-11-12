<?php

declare(strict_types=1);

namespace Phetl\Transform\Columns;

use Closure;
use Generator;
use InvalidArgumentException;

/**
 * Column addition transformations.
 */
class ColumnAdder
{
    /**
     * Add a new column with a computed value.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @param string $columnName Name of the new column
     * @param mixed|Closure $value Value or function to compute value
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function add(iterable $data, string $columnName, mixed $value): Generator
    {
        if ($columnName === '') {
            throw new InvalidArgumentException('Column name cannot be empty');
        }

        $headerProcessed = false;
        /** @var array<int|string, mixed>|null $header */
        $header = null;

        foreach ($data as $row) {
            if (!$headerProcessed) {
                // Add new column to header
                $header = $row;
                $row[] = $columnName;
                yield $row;
                $headerProcessed = true;
                continue;
            }

            // Compute value for new column
            if ($value instanceof Closure) {
                // Create associative array for easier access in closure
                $assocRow = [];
                if ($header !== null) {
                    foreach ($header as $index => $col) {
                        $assocRow[$col] = $row[$index] ?? null;
                    }
                }
                $computedValue = $value($assocRow);
            } else {
                $computedValue = $value;
            }

            $row[] = $computedValue;
            yield $row;
        }
    }

    /**
     * Add a column with a constant value.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     */
    public static function addConstant(iterable $data, string $columnName, mixed $value): Generator
    {
        return self::add($data, $columnName, $value);
    }

    /**
     * Add a row number column (1-indexed, excluding header).
     *
     * @param iterable<int, array<int|string, mixed>> $data
     */
    public static function addRowNumbers(iterable $data, string $columnName = 'row_number'): Generator
    {
        $headerProcessed = false;
        $rowNumber = 0;

        foreach ($data as $row) {
            if (!$headerProcessed) {
                // Add row number column to header
                $row[] = $columnName;
                yield $row;
                $headerProcessed = true;
                continue;
            }

            $rowNumber++;
            $row[] = $rowNumber;
            yield $row;
        }
    }
}
