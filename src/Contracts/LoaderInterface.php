<?php

declare(strict_types=1);

namespace Phetl\Contracts;

use Phetl\Support\LoadResult;

/**
 * Interface for data loaders that write tabular data to destinations.
 *
 * A loader is responsible for taking headers and data rows and
 * persisting them to a target destination (file, database, etc.).
 */
interface LoaderInterface
{
    /**
     * Load data to the destination.
     *
     * @param array<string> $headers Column names
     * @param iterable<int, array<int|string, mixed>> $data Data rows (without header)
     * @return LoadResult Result containing row count, errors, warnings, etc.
     */
    public function load(array $headers, iterable $data): LoadResult;
}
