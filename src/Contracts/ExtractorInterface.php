<?php

declare(strict_types=1);

namespace Phetl\Contracts;

/**
 * Interface for data extractors that provide tabular data.
 *
 * An extractor is responsible for reading data from a source and
 * providing it as an iterable of rows (arrays).
 */
interface ExtractorInterface
{
    /**
     * Extract data from the source.
     *
     * Returns an iterable where:
     * - First element is the header row (array of field names)
     * - Subsequent elements are data rows (arrays of values)
     *
     * @return iterable<int, array<int|string, mixed>>
     */
    public function extract(): iterable;
}
