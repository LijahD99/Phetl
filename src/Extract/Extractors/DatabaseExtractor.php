<?php

declare(strict_types=1);

namespace Phetl\Extract\Extractors;

use InvalidArgumentException;
use PDO;
use Phetl\Contracts\ExtractorInterface;

/**
 * Extracts data from database queries using PDO.
 *
 * Executes SQL queries and converts results into tabular format.
 * Supports parameterized queries for security and flexibility.
 */
final class DatabaseExtractor implements ExtractorInterface
{
    /**
     * @param PDO $pdo Database connection
     * @param string $query SQL query to execute
     * @param array<string, mixed> $params Query parameters for prepared statements
     */
    public function __construct(
        private readonly PDO $pdo,
        private readonly string $query,
        private readonly array $params = []
    ) {
        $this->validate();
    }

    /**
     * @return array{0: array<string>, 1: array<int, array<int|string, mixed>>}
     */
    public function extract(): array
    {
        $statement = $this->pdo->prepare($this->query);
        $statement->execute($this->params);

        // Get column names from result set metadata
        $columnCount = $statement->columnCount();
        $headers = [];

        for ($i = 0; $i < $columnCount; $i++) {
            $meta = $statement->getColumnMeta($i);
            $headers[] = $meta['name'] ?? "column_$i";
        }

        // Fetch all data rows
        $data = [];
        while (($row = $statement->fetch(PDO::FETCH_NUM)) !== false) {
            /** @var array<int|string, mixed> $row */
            $data[] = $row;
        }

        return [$headers, $data];
    }

    /**
     * Validate query is not empty.
     */
    private function validate(): void
    {
        if (trim($this->query) === '') {
            throw new InvalidArgumentException('SQL query cannot be empty');
        }
    }
}
