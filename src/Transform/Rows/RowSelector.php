<?php

declare(strict_types=1);

namespace Phetl\Transform\Rows;

use Generator;
use InvalidArgumentException;

/**
 * Row selection transformations for limiting and slicing table data.
 */
class RowSelector
{
    /**
     * Select the first N rows (plus header).
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function head(iterable $data, int $limit): Generator
    {
        if ($limit < 0) {
            throw new InvalidArgumentException('Limit must be non-negative');
        }

        $count = 0;

        foreach ($data as $row) {
            yield $row;
            $count++;

            // First row is header, so limit+1 total rows
            if ($count > $limit) {
                break;
            }
        }
    }

    /**
     * Select the last N rows (plus header).
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function tail(iterable $data, int $limit): Generator
    {
        if ($limit < 0) {
            throw new InvalidArgumentException('Limit must be non-negative');
        }

        // Materialize to get last N rows
        $rows = is_array($data) ? $data : iterator_to_array($data, false);

        if ($rows === []) {
            return;
        }

        // First row is always the header
        yield $rows[0];

        if ($limit === 0) {
            return;
        }

        // Get last N data rows
        $dataRows = array_slice($rows, 1);
        $lastRows = array_slice($dataRows, -$limit);

        foreach ($lastRows as $row) {
            yield $row;
        }
    }

    /**
     * Select a slice of rows by range (excluding header).
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function slice(iterable $data, int $start, ?int $stop = null, int $step = 1): Generator
    {
        if ($start < 0) {
            throw new InvalidArgumentException('Start index must be non-negative');
        }

        if ($stop !== null && $stop < $start) {
            throw new InvalidArgumentException('Stop index must be greater than or equal to start');
        }

        if ($step < 1) {
            throw new InvalidArgumentException('Step must be positive');
        }

        $rowIndex = 0;
        $outputIndex = 0;

        foreach ($data as $row) {
            // Always yield header
            if ($rowIndex === 0) {
                yield $row;
                $rowIndex++;
                continue;
            }

            $dataIndex = $rowIndex - 1; // Exclude header from index

            // Check if this row is in range
            if ($dataIndex >= $start && ($stop === null || $dataIndex < $stop)) {
                // Check if this row matches step
                if (($dataIndex - $start) % $step === 0) {
                    yield $row;
                    $outputIndex++;
                }
            }

            // Stop early if we've passed the stop index
            if ($stop !== null && $dataIndex >= $stop) {
                break;
            }

            $rowIndex++;
        }
    }

    /**
     * Skip the first N rows (after header).
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function skip(iterable $data, int $count): Generator
    {
        if ($count < 0) {
            throw new InvalidArgumentException('Count must be non-negative');
        }

        $rowIndex = 0;

        foreach ($data as $row) {
            // Always yield header
            if ($rowIndex === 0) {
                yield $row;
                $rowIndex++;
                continue;
            }

            $dataIndex = $rowIndex - 1;

            // Skip first N data rows
            if ($dataIndex >= $count) {
                yield $row;
            }

            $rowIndex++;
        }
    }
}
