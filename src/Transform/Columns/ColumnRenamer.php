<?php

declare(strict_types=1);

namespace Phetl\Transform\Columns;

use Generator;
use InvalidArgumentException;

/**
 * Column renaming transformations.
 */
class ColumnRenamer
{
    /**
     * Rename columns using a mapping array.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @param array<string, string> $mapping Old name => New name
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function rename(iterable $data, array $mapping): Generator
    {
        if ($mapping === []) {
            yield from $data;
            return;
        }

        $headerProcessed = false;

        foreach ($data as $row) {
            if (!$headerProcessed) {
                // Rename header columns
                $newHeader = [];
                foreach ($row as $column) {
                    $newHeader[] = $mapping[$column] ?? $column;
                }
                yield $newHeader;
                $headerProcessed = true;
                continue;
            }

            // Data rows pass through unchanged
            yield $row;
        }
    }

    /**
     * Rename a single column.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     */
    public static function renameColumn(iterable $data, string $oldName, string $newName): Generator
    {
        return self::rename($data, [$oldName => $newName]);
    }
}
