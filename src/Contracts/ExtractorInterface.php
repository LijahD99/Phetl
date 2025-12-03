<?php

declare(strict_types=1);

namespace Phetl\Contracts;

/**
 * Interface for data extractors that provide tabular data.
 *
 * An extractor is responsible for reading data from a source and
 * providing it as headers and data rows separately.
 */
interface ExtractorInterface
{
    /**
     * Extract data from the source.
     *
     * Returns a tuple:
     * - Index 0: array of column names (headers)
     * - Index 1: iterable of data rows (without header)
     *
     * @return array{0: array<string>, 1: iterable<int, array<int|string, mixed>>}
     */
    public function extract(): array;
}
