<?php

declare(strict_types=1);

namespace Phetl\Extract\Extractors;

use InvalidArgumentException;
use Phetl\Contracts\ExtractorInterface;

/**
 * Extracts data from a PHP array.
 *
 * Provides a simple way to create a table from in-memory array data.
 * The first row must be the header (field names), followed by data rows.
 */
final class ArrayExtractor implements ExtractorInterface
{
    /**
     * @param array<int, array<int|string, mixed>> $data
     */
    public function __construct(
        private readonly array $data
    ) {
        $this->validate();
    }

    /**
     * @return iterable<int, array<int|string, mixed>>
     */
    public function extract(): iterable
    {
        foreach ($this->data as $row) {
            yield $row;
        }
    }

    /**
     * Validate that data is properly structured.
     */
    private function validate(): void
    {
        if ($this->data === []) {
            throw new InvalidArgumentException('Data array must contain at least a header row');
        }
    }
}
