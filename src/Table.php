<?php

declare(strict_types=1);

namespace Phetl;

use IteratorAggregate;
use Phetl\Contracts\ExtractorInterface;
use Phetl\Contracts\LoaderInterface;
use Phetl\Extract\Extractors\ArrayExtractor;
use Phetl\Extract\Extractors\CsvExtractor;
use Phetl\Extract\Extractors\DatabaseExtractor;
use Phetl\Extract\Extractors\JsonExtractor;
use Phetl\Load\Loaders\CsvLoader;
use Phetl\Load\Loaders\DatabaseLoader;
use Phetl\Load\Loaders\JsonLoader;
use Phetl\Transform\Rows\RowSelector;
use PDO;
use Traversable;

/**
 * Main Table class for PHETL ETL operations.
 *
 * Wraps an iterable data source and provides fluent API for transformations.
 * First row is expected to be headers, subsequent rows are data.
 *
 * @implements IteratorAggregate<int, array<int|string, mixed>>
 */
class Table implements IteratorAggregate
{
    /**
     * @var array<int, array<int|string, mixed>>
     */
    private readonly array $materializedData;

    /**
     * @param iterable<int, array<int|string, mixed>> $data
     */
    public function __construct(
        iterable $data
    ) {
        // Materialize the data to allow multiple iterations
        $this->materializedData = is_array($data) ? $data : iterator_to_array($data, false);
    }

    /**
     * Create a Table from an array.
     *
     * @param array<int, array<int|string, mixed>> $data
     */
    public static function fromArray(array $data): self
    {
        $extractor = new ArrayExtractor($data);
        return new self($extractor->extract());
    }

    /**
     * Create a Table from a CSV file.
     */
    public static function fromCsv(
        string $filePath,
        string $delimiter = ',',
        string $enclosure = '"',
        string $escape = '\\'
    ): self {
        $extractor = new CsvExtractor($filePath, $delimiter, $enclosure, $escape);
        return new self($extractor->extract());
    }

    /**
     * Create a Table from a JSON file.
     */
    public static function fromJson(string $filePath): self
    {
        $extractor = new JsonExtractor($filePath);
        return new self($extractor->extract());
    }

    /**
     * Create a Table from a database query.
     *
     * @param array<int|string, mixed> $params
     */
    public static function fromDatabase(PDO $pdo, string $query, array $params = []): self
    {
        $extractor = new DatabaseExtractor($pdo, $query, $params);
        return new self($extractor->extract());
    }

    /**
     * Create a Table from any extractor.
     */
    public static function fromExtractor(ExtractorInterface $extractor): self
    {
        return new self($extractor->extract());
    }

    /**
     * Load data to a CSV file.
     */
    public function toCsv(
        string $filePath,
        string $delimiter = ',',
        string $enclosure = '"',
        string $escape = '\\'
    ): int {
        $loader = new CsvLoader($filePath, $delimiter, $enclosure, $escape);
        return $loader->load($this->materializedData);
    }

    /**
     * Load data to a JSON file.
     */
    public function toJson(string $filePath, bool $prettyPrint = false): int
    {
        $loader = new JsonLoader($filePath, $prettyPrint);
        return $loader->load($this->materializedData);
    }

    /**
     * Load data to a database table.
     */
    public function toDatabase(PDO $pdo, string $tableName): int
    {
        $loader = new DatabaseLoader($pdo, $tableName);
        return $loader->load($this->materializedData);
    }

    /**
     * Load data using any loader.
     */
    public function toLoader(LoaderInterface $loader): int
    {
        return $loader->load($this->materializedData);
    }

    /**
     * Get the underlying data as an array (materializes all rows).
     *
     * @return array<int, array<int|string, mixed>>
     */
    public function toArray(): array
    {
        return iterator_to_array($this->getIterator(), false);
    }

    /**
     * Get iterator for the table data.
     *
     * @return Traversable<int, array<int|string, mixed>>
     */
    public function getIterator(): Traversable
    {
        yield from $this->materializedData;
    }

    /**
     * Display first N rows (for debugging/inspection).
     *
     * @return array<int, array<int|string, mixed>>
     */
    public function look(int $limit = 10): array
    {
        $rows = [];
        $count = 0;

        foreach ($this->materializedData as $row) {
            $rows[] = $row;
            $count++;

            if ($count >= $limit) {
                break;
            }
        }

        return $rows;
    }

    /**
     * Count the number of rows (including header).
     */
    public function count(): int
    {
        return count($this->materializedData);
    }

    /**
     * Get the header row (first row).
     *
     * @return array<int|string, mixed>
     */
    public function header(): array
    {
        if ($this->materializedData === []) {
            return [];
        }

        return $this->materializedData[0];
    }

    // ==================== TRANSFORMATIONS ====================

    /**
     * Select the first N rows (plus header).
     */
    public function head(int $limit): self
    {
        return new self(RowSelector::head($this->materializedData, $limit));
    }

    /**
     * Select the last N rows (plus header).
     */
    public function tail(int $limit): self
    {
        return new self(RowSelector::tail($this->materializedData, $limit));
    }

    /**
     * Select a slice of rows by range.
     * Start and stop indices exclude the header (0-indexed data rows).
     */
    public function slice(int $start, ?int $stop = null, int $step = 1): self
    {
        return new self(RowSelector::slice($this->materializedData, $start, $stop, $step));
    }

    /**
     * Skip the first N data rows (header is preserved).
     */
    public function skip(int $count): self
    {
        return new self(RowSelector::skip($this->materializedData, $count));
    }
}
