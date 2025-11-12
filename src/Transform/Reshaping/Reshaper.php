<?php

declare(strict_types=1);

namespace Phetl\Transform\Reshaping;

use Generator;
use InvalidArgumentException;

/**
 * Reshaping operations for transforming table structure.
 */
class Reshaper
{
    /**
     * Unpivot (melt) table from wide to long format.
     * Converts columns into rows.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @param string|array<string> $idFields Field(s) to keep as identifiers
     * @param string|array<string>|null $valueFields Field(s) to unpivot (null = all except id fields)
     * @param string $variableName Name for the variable column (default: 'variable')
     * @param string $valueName Name for the value column (default: 'value')
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function unpivot(
        iterable $data,
        string|array $idFields,
        string|array|null $valueFields = null,
        string $variableName = 'variable',
        string $valueName = 'value'
    ): Generator {
        $idFields = is_array($idFields) ? $idFields : [$idFields];

        $tableData = self::processTable($data);
        $idIndices = self::getFieldIndices($tableData['header'], $idFields);

        // Determine value fields
        if ($valueFields === null) {
            // All fields except id fields
            $valueIndices = [];
            foreach ($tableData['header'] as $index => $field) {
                if (!in_array($index, $idIndices, true)) {
                    $valueIndices[$index] = $field;
                }
            }
        } else {
            $valueFields = is_array($valueFields) ? $valueFields : [$valueFields];
            $valueIndices = [];
            foreach ($valueFields as $field) {
                $index = array_search($field, $tableData['header'], true);
                if ($index === false) {
                    throw new InvalidArgumentException("Field '$field' not found in header");
                }
                $valueIndices[$index] = $field;
            }
        }

        // Yield new header
        yield array_merge($idFields, [$variableName, $valueName]);

        // Unpivot rows
        foreach ($tableData['rows'] as $row) {
            // Extract id values
            $idValues = [];
            foreach ($idIndices as $idIndex) {
                $idValues[] = $row[$idIndex] ?? null;
            }

            // Create one row per value field
            foreach ($valueIndices as $valueIndex => $fieldName) {
                yield array_merge($idValues, [$fieldName, $row[$valueIndex] ?? null]);
            }
        }
    }

    /**
     * Alias for unpivot - petl compatibility.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @param string|array<string> $idFields Field(s) to keep as identifiers
     * @param string|array<string>|null $valueFields Field(s) to melt (null = all except id fields)
     * @param string $variableName Name for the variable column (default: 'variable')
     * @param string $valueName Name for the value column (default: 'value')
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function melt(
        iterable $data,
        string|array $idFields,
        string|array|null $valueFields = null,
        string $variableName = 'variable',
        string $valueName = 'value'
    ): Generator {
        yield from self::unpivot($data, $idFields, $valueFields, $variableName, $valueName);
    }

    /**
     * Pivot table from long to wide format.
     * Converts rows into columns.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @param string|array<string> $indexFields Field(s) to use as row identifiers
     * @param string $columnField Field to pivot into columns
     * @param string $valueField Field to use for values
     * @param callable|string|null $aggregation Aggregation function for duplicate combinations (default: first value)
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function pivot(
        iterable $data,
        string|array $indexFields,
        string $columnField,
        string $valueField,
        callable|string|null $aggregation = null
    ): Generator {
        $indexFields = is_array($indexFields) ? $indexFields : [$indexFields];

        $tableData = self::processTable($data);
        $indexIndices = self::getFieldIndices($tableData['header'], $indexFields);
        $columnIndex = self::getFieldIndex($tableData['header'], $columnField);
        $valueIndex = self::getFieldIndex($tableData['header'], $valueField);

        // Collect unique column values and build pivot structure
        $columnValues = [];
        $pivotData = [];

        foreach ($tableData['rows'] as $row) {
            // Extract index key
            $indexKey = serialize(array_map(fn($i) => $row[$i] ?? null, $indexIndices));

            // Extract column and value
            $colValue = $row[$columnIndex] ?? null;
            $value = $row[$valueIndex] ?? null;

            // Track unique column values
            if (!in_array($colValue, $columnValues, true)) {
                $columnValues[] = $colValue;
            }

            // Store data
            if (!isset($pivotData[$indexKey])) {
                $pivotData[$indexKey] = [
                    'index_values' => array_map(fn($i) => $row[$i] ?? null, $indexIndices),
                    'columns' => [],
                ];
            }

            // Handle aggregation if key already exists
            if (isset($pivotData[$indexKey]['columns'][$colValue])) {
                if ($aggregation !== null) {
                    $existing = $pivotData[$indexKey]['columns'][$colValue];
                    $pivotData[$indexKey]['columns'][$colValue] = self::applyAggregation(
                        $aggregation,
                        [$existing, $value]
                    );
                }
                // Otherwise keep first value (default behavior)
            } else {
                $pivotData[$indexKey]['columns'][$colValue] = $value;
            }
        }

        // Sort column values for consistent output
        sort($columnValues);

        // Yield header
        yield array_merge($indexFields, $columnValues);

        // Yield pivoted rows
        foreach ($pivotData as $data) {
            $row = $data['index_values'];

            // Add values for each column (null if missing)
            foreach ($columnValues as $colValue) {
                $row[] = $data['columns'][$colValue] ?? null;
            }

            yield $row;
        }
    }

    /**
     * Transpose table - swap rows and columns.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @return Generator<int, array<int|string, mixed>>
     */
    public static function transpose(iterable $data): Generator
    {
        $rows = [];

        foreach ($data as $row) {
            $rows[] = $row;
        }

        if (empty($rows)) {
            return;
        }

        // Determine max columns
        $maxCols = max(array_map('count', $rows));

        // Transpose
        for ($col = 0; $col < $maxCols; $col++) {
            $newRow = [];
            foreach ($rows as $row) {
                $newRow[] = $row[$col] ?? null;
            }
            yield $newRow;
        }
    }

    /**
     * Process table into header and rows.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @return array{header: array<int|string, mixed>, rows: array<int, array<int|string, mixed>>}
     */
    private static function processTable(iterable $data): array
    {
        $header = null;
        $rows = [];

        foreach ($data as $index => $row) {
            if ($index === 0) {
                $header = $row;
                continue;
            }
            $rows[] = $row;
        }

        if ($header === null) {
            throw new InvalidArgumentException('Table must have a header row');
        }

        return [
            'header' => $header,
            'rows' => $rows,
        ];
    }

    /**
     * Get field indices from header.
     *
     * @param array<int|string, mixed> $header
     * @param array<string> $fields
     * @return array<int|string>
     */
    private static function getFieldIndices(array $header, array $fields): array
    {
        $indices = [];

        foreach ($fields as $field) {
            $index = array_search($field, $header, true);
            if ($index === false) {
                throw new InvalidArgumentException("Field '$field' not found in header");
            }
            $indices[] = $index;
        }

        return $indices;
    }

    /**
     * Get single field index from header.
     *
     * @param array<int|string, mixed> $header
     * @param string $field
     * @return int|string
     */
    private static function getFieldIndex(array $header, string $field): int|string
    {
        $index = array_search($field, $header, true);
        if ($index === false) {
            throw new InvalidArgumentException("Field '$field' not found in header");
        }
        return $index;
    }

    /**
     * Apply aggregation to values.
     *
     * @param callable|string $aggregation
     * @param array<mixed> $values
     * @return mixed
     */
    private static function applyAggregation(callable|string $aggregation, array $values): mixed
    {
        if (is_string($aggregation)) {
            return match ($aggregation) {
                'sum' => array_sum($values),
                'avg', 'average', 'mean' => array_sum($values) / count($values),
                'min' => min($values),
                'max' => max($values),
                'count' => count($values),
                'first' => $values[0] ?? null,
                'last' => $values[array_key_last($values)] ?? null,
                default => throw new InvalidArgumentException("Unknown aggregation: $aggregation"),
            };
        }

        return $aggregation($values);
    }
}
