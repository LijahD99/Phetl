<?php

declare(strict_types=1);

namespace Phetl\Tests\Unit\Extract\Extractors;

use InvalidArgumentException;
use PDO;
use Phetl\Contracts\ExtractorInterface;
use Phetl\Extract\Extractors\DatabaseExtractor;
use PHPUnit\Framework\TestCase;

final class DatabaseExtractorTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        parent::setUp();

        // Create in-memory SQLite database for testing
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Create test table
        $this->pdo->exec('
            CREATE TABLE users (
                id INTEGER PRIMARY KEY,
                name TEXT NOT NULL,
                age INTEGER,
                city TEXT
            )
        ');

        // Insert test data
        $this->pdo->exec("
            INSERT INTO users (name, age, city) VALUES
            ('Alice', 30, 'NYC'),
            ('Bob', 25, 'LA'),
            ('Charlie', 35, 'Chicago')
        ");
    }

    public function test_it_implements_extractor_interface(): void
    {
        $extractor = new DatabaseExtractor($this->pdo, 'SELECT * FROM users');

        $this->assertInstanceOf(ExtractorInterface::class, $extractor);
    }

    public function test_it_extracts_database_query_results(): void
    {
        $extractor = new DatabaseExtractor($this->pdo, 'SELECT name, age FROM users ORDER BY id');
        [$headers, $data] = $extractor->extract();

        $this->assertEquals(['name', 'age'], $headers);
        $this->assertCount(3, $data);
        $this->assertEquals(['Alice', 30], $data[0]);
        $this->assertEquals(['Bob', 25], $data[1]);
        $this->assertEquals(['Charlie', 35], $data[2]);
    }

    public function test_it_validates_non_empty_query(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('SQL query cannot be empty');

        new DatabaseExtractor($this->pdo, '');
    }

    public function test_it_yields_rows_lazily(): void
    {
        $extractor = new DatabaseExtractor($this->pdo, 'SELECT * FROM users');
        $result = $extractor->extract();

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    public function test_it_handles_parameterized_queries(): void
    {
        $extractor = new DatabaseExtractor(
            $this->pdo,
            'SELECT name, age FROM users WHERE age > :min_age ORDER BY id',
            ['min_age' => 25]
        );
        [$headers, $data] = $extractor->extract();

        $this->assertEquals(['name', 'age'], $headers);
        $this->assertCount(2, $data);
        $this->assertEquals(['Alice', 30], $data[0]);
        $this->assertEquals(['Charlie', 35], $data[1]);
    }

    public function test_it_handles_empty_result_set(): void
    {
        $extractor = new DatabaseExtractor(
            $this->pdo,
            'SELECT * FROM users WHERE age > 100'
        );
        [$headers, $data] = $extractor->extract();

        // Should still have header row
        $this->assertEquals(['id', 'name', 'age', 'city'], $headers);
        $this->assertCount(0, $data);
    }

    public function test_it_can_be_iterated_multiple_times(): void
    {
        $extractor = new DatabaseExtractor($this->pdo, 'SELECT name FROM users ORDER BY id');

        $result1 = $extractor->extract();
        $result2 = $extractor->extract();

        $this->assertEquals($result1, $result2);
    }

    public function test_it_handles_all_column_types(): void
    {
        // Create table with various types
        $this->pdo->exec('
            CREATE TABLE test_types (
                int_col INTEGER,
                text_col TEXT,
                real_col REAL,
                blob_col BLOB
            )
        ');

        $this->pdo->exec("
            INSERT INTO test_types VALUES (42, 'hello', 3.14, X'DEADBEEF')
        ");

        $extractor = new DatabaseExtractor($this->pdo, 'SELECT * FROM test_types');
        [$headers, $data] = $extractor->extract();

        $this->assertEquals(['int_col', 'text_col', 'real_col', 'blob_col'], $headers);
        $this->assertCount(1, $data);
        $this->assertEquals(42, $data[0][0]);
        $this->assertEquals('hello', $data[0][1]);
        $this->assertEquals(3.14, $data[0][2]);
    }

    public function test_it_preserves_null_values(): void
    {
        $this->pdo->exec("INSERT INTO users (name, age, city) VALUES ('Dave', NULL, NULL)");

        $extractor = new DatabaseExtractor($this->pdo, 'SELECT name, age, city FROM users WHERE name = "Dave"');
        [$headers, $data] = $extractor->extract();

        $this->assertCount(1, $data);
        $this->assertEquals(['Dave', null, null], $data[0]);
    }

    public function test_it_handles_special_characters_in_data(): void
    {
        $this->pdo->exec("INSERT INTO users (name, age, city) VALUES ('O''Brien', 28, 'San Francisco')");

        $extractor = new DatabaseExtractor($this->pdo, 'SELECT name FROM users WHERE name = \'O\'\'Brien\'');
        [$headers, $data] = $extractor->extract();

        $this->assertCount(1, $data);
        $this->assertEquals(["O'Brien"], $data[0]);
    }

    public function test_it_throws_exception_on_invalid_query(): void
    {
        $this->expectException(\PDOException::class);

        $extractor = new DatabaseExtractor($this->pdo, 'SELECT * FROM nonexistent_table');
        $extractor->extract();
    }
}
