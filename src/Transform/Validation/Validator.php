<?php

declare(strict_types=1);

namespace Phetl\Transform\Validation;

use Generator;
use InvalidArgumentException;

/**
 * Validation operations for data quality checks.
 */
class Validator
{
    /**
     * Validate that required fields have non-null, non-empty values.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @param array<string> $fields
     * @return array{valid: bool, errors: array<int, array{row: int, field: string, rule: string, message: string}>}
     */
    public static function required(iterable $data, array $fields): array
    {
        $tableData = self::processTable($data);
        $fieldIndices = self::getFieldIndices($tableData['header'], $fields);
        $errors = [];

        foreach ($tableData['rows'] as $rowIndex => $row) {
            foreach ($fields as $i => $field) {
                $index = $fieldIndices[$i];
                $value = $row[$index] ?? null;

                if ($value === null || $value === '') {
                    $errors[] = [
                        'row' => $rowIndex + 1,  // 1-based for user display
                        'field' => $field,
                        'rule' => 'required',
                        'message' => "Field \"$field\" is required",
                    ];
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Validate field values match expected type.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @param string $field
     * @param string $type Type: int|integer|float|double|string|bool|boolean|array|object|null
     * @return array{valid: bool, errors: array<int, array{row: int, field: string, rule: string, message: string, expected: string, actual: string}>}
     */
    public static function type(iterable $data, string $field, string $type): array
    {
        $tableData = self::processTable($data);
        $fieldIndex = self::getFieldIndex($tableData['header'], $field);
        $errors = [];

        // Normalize type names
        $typeMap = [
            'int' => 'integer',
            'bool' => 'boolean',
            'float' => 'double',
        ];
        $expectedType = $typeMap[$type] ?? $type;

        foreach ($tableData['rows'] as $rowIndex => $row) {
            $value = $row[$fieldIndex] ?? null;
            $actualType = gettype($value);

            // Normalize actual type
            $actualType = $typeMap[strtolower($actualType)] ?? strtolower($actualType);

            if ($actualType !== $expectedType) {
                $errors[] = [
                    'row' => $rowIndex + 1,
                    'field' => $field,
                    'rule' => 'type',
                    'message' => "Field \"$field\" must be of type $expectedType",
                    'expected' => $expectedType,
                    'actual' => $actualType,
                ];
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Validate field values are within range.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @param string $field
     * @param int|float|null $min
     * @param int|float|null $max
     * @return array{valid: bool, errors: array<int, array{row: int, field: string, rule: string, message: string, value: mixed, min?: int|float, max?: int|float}>}
     */
    public static function range(iterable $data, string $field, int|float|null $min, int|float|null $max): array
    {
        $tableData = self::processTable($data);
        $fieldIndex = self::getFieldIndex($tableData['header'], $field);
        $errors = [];

        foreach ($tableData['rows'] as $rowIndex => $row) {
            $value = $row[$fieldIndex] ?? null;

            if ($min !== null && $value < $min) {
                $error = [
                    'row' => $rowIndex + 1,
                    'field' => $field,
                    'rule' => 'range',
                    'message' => "Field \"$field\" must be >= $min",
                    'value' => $value,
                    'min' => $min,
                ];
                $errors[] = $error;
            }

            if ($max !== null && $value > $max) {
                $error = [
                    'row' => $rowIndex + 1,
                    'field' => $field,
                    'rule' => 'range',
                    'message' => "Field \"$field\" must be <= $max",
                    'value' => $value,
                    'max' => $max,
                ];
                $errors[] = $error;
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Validate field values match regex pattern.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @param string $field
     * @param string $pattern
     * @return array{valid: bool, errors: array<int, array{row: int, field: string, rule: string, message: string, pattern: string, value: mixed}>}
     */
    public static function pattern(iterable $data, string $field, string $pattern): array
    {
        $tableData = self::processTable($data);
        $fieldIndex = self::getFieldIndex($tableData['header'], $field);
        $errors = [];

        foreach ($tableData['rows'] as $rowIndex => $row) {
            $value = $row[$fieldIndex] ?? null;

            if (!preg_match($pattern, (string)$value)) {
                $errors[] = [
                    'row' => $rowIndex + 1,
                    'field' => $field,
                    'rule' => 'pattern',
                    'message' => "Field \"$field\" must match pattern $pattern",
                    'pattern' => $pattern,
                    'value' => $value,
                ];
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Validate field values are in allowed list.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @param string $field
     * @param array<mixed> $allowedValues
     * @return array{valid: bool, errors: array<int, array{row: int, field: string, rule: string, message: string, value: mixed, allowed: array<mixed>}>}
     */
    public static function in(iterable $data, string $field, array $allowedValues): array
    {
        $tableData = self::processTable($data);
        $fieldIndex = self::getFieldIndex($tableData['header'], $field);
        $errors = [];

        foreach ($tableData['rows'] as $rowIndex => $row) {
            $value = $row[$fieldIndex] ?? null;

            if (!in_array($value, $allowedValues, true)) {
                $errors[] = [
                    'row' => $rowIndex + 1,
                    'field' => $field,
                    'rule' => 'in',
                    'message' => "Field \"$field\" must be one of: " . implode(', ', $allowedValues),
                    'value' => $value,
                    'allowed' => $allowedValues,
                ];
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Validate field values with custom function.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @param string $field
     * @param callable $validator Function that returns true if valid
     * @param string $message Error message
     * @return array{valid: bool, errors: array<int, array{row: int, field: string, rule: string, message: string, value: mixed}>}
     */
    public static function custom(iterable $data, string $field, callable $validator, string $message): array
    {
        $tableData = self::processTable($data);
        $fieldIndex = self::getFieldIndex($tableData['header'], $field);
        $errors = [];

        foreach ($tableData['rows'] as $rowIndex => $row) {
            $value = $row[$fieldIndex] ?? null;

            if (!$validator($value)) {
                $errors[] = [
                    'row' => $rowIndex + 1,
                    'field' => $field,
                    'rule' => 'custom',
                    'message' => $message,
                    'value' => $value,
                ];
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Validate field values are unique.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @param string $field
     * @return array{valid: bool, errors: array<int, array{row: int, field: string, rule: string, message: string, value: mixed}>}
     */
    public static function unique(iterable $data, string $field): array
    {
        $tableData = self::processTable($data);
        $fieldIndex = self::getFieldIndex($tableData['header'], $field);
        $errors = [];
        $seen = [];

        foreach ($tableData['rows'] as $rowIndex => $row) {
            $value = $row[$fieldIndex] ?? null;
            $key = serialize($value);

            if (isset($seen[$key])) {
                $errors[] = [
                    'row' => $rowIndex + 1,
                    'field' => $field,
                    'rule' => 'unique',
                    'message' => "Field \"$field\" must be unique",
                    'value' => $value,
                ];
            } else {
                $seen[$key] = true;
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Validate multiple rules at once.
     *
     * @param iterable<int, array<int|string, mixed>> $data
     * @param array<string, array<int, string|array<int, mixed>>> $rules
     * @return array{valid: bool, errors: array<int, array{row: int, field: string, rule: string, message: string}>}
     */
    public static function validate(iterable $data, array $rules): array
    {
        $allErrors = [];

        foreach ($rules as $field => $fieldRules) {
            foreach ($fieldRules as $rule) {
                $result = match (true) {
                    $rule === 'required' => self::required($data, [$field]),
                    is_array($rule) && $rule[0] === 'type' => self::type($data, $field, $rule[1]),
                    is_array($rule) && $rule[0] === 'range' => self::range($data, $field, $rule[1] ?? null, $rule[2] ?? null),
                    is_array($rule) && $rule[0] === 'pattern' => self::pattern($data, $field, $rule[1]),
                    is_array($rule) && $rule[0] === 'in' => self::in($data, $field, $rule[1]),
                    is_array($rule) && $rule[0] === 'custom' => self::custom($data, $field, $rule[1], $rule[2] ?? 'Validation failed'),
                    is_array($rule) && $rule[0] === 'unique' => self::unique($data, $field),
                    default => ['valid' => true, 'errors' => []],
                };

                $allErrors = array_merge($allErrors, $result['errors']);
            }
        }

        return [
            'valid' => empty($allErrors),
            'errors' => $allErrors,
        ];
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
     * Get field index from header.
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
            $indices[] = self::getFieldIndex($header, $field);
        }

        return $indices;
    }
}
