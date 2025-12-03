<?php

declare(strict_types=1);

namespace Phetl\Extract\Extractors;

use InvalidArgumentException;
use Phetl\Contracts\ExtractorInterface;

/**
 * Extracts data from a PHP array.
 *
 * Provides a simple way to create a table from in-memory array data.
 * Supports two formats:
 * 1. First row is header (backward compatible)
 * 2. Explicit headers provided separately (recommended)
 */
final class ArrayExtractor implements ExtractorInterface
{
    /**
     * @param array<int, array<int|string, mixed>> $data
     * @param array<string>|null $headers Explicit headers (null = first row is header)
     */
    public function __construct(
        private readonly array $data,
        private readonly ?array $headers = null
    ) {
        $this->validate();
    }

    /**
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public function extract(): array
    {
        if ($this->headers !== null) {
            // Explicit headers provided
            return [$this->headers, $this->data];
        }

        // Backward compatible: first row is header
        if ($this->data === []) {
            return [[], []];
        }

        $headers = array_values(array_map('strval', $this->data[0]));
        $dataRows = array_slice($this->data, 1);

        return [$headers, $dataRows];
    }

    /**
     * Validate that data is properly structured.
     */
    private function validate(): void
    {
        if ($this->headers === null && $this->data === []) {
            throw new InvalidArgumentException('Data array must contain at least a header row when headers are not provided');
        }
    }
}
