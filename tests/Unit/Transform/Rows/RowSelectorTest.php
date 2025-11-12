<?php

declare(strict_types=1);

namespace Phetl\Tests\Unit\Transform\Rows;

use InvalidArgumentException;
use Phetl\Table;
use PHPUnit\Framework\TestCase;

class RowSelectorTest extends TestCase
{
    public function test_it_selects_first_n_rows_with_head(): void
    {
        $data = [
            ['name', 'age'],
            ['Alice', 30],
            ['Bob', 25],
            ['Charlie', 35],
            ['David', 40],
        ];

        $table = Table::fromArray($data)->head(2);

        $result = $table->toArray();
        $this->assertCount(3, $result); // header + 2 rows
        $this->assertEquals(['name', 'age'], $result[0]);
        $this->assertEquals(['Alice', 30], $result[1]);
        $this->assertEquals(['Bob', 25], $result[2]);
    }

    public function test_it_returns_all_rows_when_head_limit_exceeds_count(): void
    {
        $data = [
            ['name', 'age'],
            ['Alice', 30],
        ];

        $table = Table::fromArray($data)->head(10);

        $result = $table->toArray();
        $this->assertCount(2, $result);
    }

    public function test_it_returns_header_only_with_head_zero(): void
    {
        $data = [
            ['name', 'age'],
            ['Alice', 30],
        ];

        $table = Table::fromArray($data)->head(0);

        $result = $table->toArray();
        $this->assertCount(1, $result);
        $this->assertEquals(['name', 'age'], $result[0]);
    }

    public function test_it_throws_on_negative_head_limit(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $data = [['name'], ['Alice']];
        Table::fromArray($data)->head(-1)->toArray();
    }

    public function test_it_selects_last_n_rows_with_tail(): void
    {
        $data = [
            ['name', 'age'],
            ['Alice', 30],
            ['Bob', 25],
            ['Charlie', 35],
            ['David', 40],
        ];

        $table = Table::fromArray($data)->tail(2);

        $result = $table->toArray();
        $this->assertCount(3, $result); // header + 2 rows
        $this->assertEquals(['name', 'age'], $result[0]);
        $this->assertEquals(['Charlie', 35], $result[1]);
        $this->assertEquals(['David', 40], $result[2]);
    }

    public function test_it_returns_all_rows_when_tail_limit_exceeds_count(): void
    {
        $data = [
            ['name', 'age'],
            ['Alice', 30],
        ];

        $table = Table::fromArray($data)->tail(10);

        $result = $table->toArray();
        $this->assertCount(2, $result);
    }

    public function test_it_returns_header_only_with_tail_zero(): void
    {
        $data = [
            ['name', 'age'],
            ['Alice', 30],
            ['Bob', 25],
        ];

        $table = Table::fromArray($data)->tail(0);

        $result = $table->toArray();
        $this->assertCount(1, $result);
        $this->assertEquals(['name', 'age'], $result[0]);
    }

    public function test_it_throws_on_negative_tail_limit(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $data = [['name'], ['Alice']];
        Table::fromArray($data)->tail(-1)->toArray();
    }

    public function test_it_slices_rows_by_range(): void
    {
        $data = [
            ['name', 'age'],
            ['Alice', 30],   // index 0
            ['Bob', 25],     // index 1
            ['Charlie', 35], // index 2
            ['David', 40],   // index 3
            ['Eve', 28],     // index 4
        ];

        $table = Table::fromArray($data)->slice(1, 4);

        $result = $table->toArray();
        $this->assertCount(4, $result); // header + 3 rows
        $this->assertEquals(['name', 'age'], $result[0]);
        $this->assertEquals(['Bob', 25], $result[1]);
        $this->assertEquals(['Charlie', 35], $result[2]);
        $this->assertEquals(['David', 40], $result[3]);
    }

    public function test_it_slices_with_step(): void
    {
        $data = [
            ['num'],
            [0],
            [1],
            [2],
            [3],
            [4],
            [5],
        ];

        $table = Table::fromArray($data)->slice(0, 6, 2);

        $result = $table->toArray();
        $this->assertCount(4, $result); // header + 0, 2, 4
        $this->assertEquals([0], $result[1]);
        $this->assertEquals([2], $result[2]);
        $this->assertEquals([4], $result[3]);
    }

    public function test_it_slices_without_stop_index(): void
    {
        $data = [
            ['name'],
            ['Alice'],
            ['Bob'],
            ['Charlie'],
        ];

        $table = Table::fromArray($data)->slice(1);

        $result = $table->toArray();
        $this->assertCount(3, $result); // header + Bob, Charlie
        $this->assertEquals(['Bob'], $result[1]);
        $this->assertEquals(['Charlie'], $result[2]);
    }

    public function test_it_throws_on_negative_slice_start(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $data = [['name'], ['Alice']];
        Table::fromArray($data)->slice(-1)->toArray();
    }

    public function test_it_throws_on_invalid_slice_range(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $data = [['name'], ['Alice']];
        Table::fromArray($data)->slice(5, 2)->toArray();
    }

    public function test_it_throws_on_invalid_slice_step(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $data = [['name'], ['Alice']];
        Table::fromArray($data)->slice(0, 5, 0)->toArray();
    }

    public function test_it_skips_first_n_rows(): void
    {
        $data = [
            ['name', 'age'],
            ['Alice', 30],
            ['Bob', 25],
            ['Charlie', 35],
        ];

        $table = Table::fromArray($data)->skip(2);

        $result = $table->toArray();
        $this->assertCount(2, $result); // header + Charlie
        $this->assertEquals(['name', 'age'], $result[0]);
        $this->assertEquals(['Charlie', 35], $result[1]);
    }

    public function test_it_returns_header_only_when_skipping_all_rows(): void
    {
        $data = [
            ['name', 'age'],
            ['Alice', 30],
        ];

        $table = Table::fromArray($data)->skip(10);

        $result = $table->toArray();
        $this->assertCount(1, $result);
        $this->assertEquals(['name', 'age'], $result[0]);
    }

    public function test_it_returns_all_rows_when_skipping_zero(): void
    {
        $data = [
            ['name', 'age'],
            ['Alice', 30],
        ];

        $table = Table::fromArray($data)->skip(0);

        $result = $table->toArray();
        $this->assertCount(2, $result);
    }

    public function test_it_throws_on_negative_skip_count(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $data = [['name'], ['Alice']];
        Table::fromArray($data)->skip(-1)->toArray();
    }

    public function test_transformations_can_be_chained(): void
    {
        $data = [
            ['name', 'age'],
            ['Alice', 30],
            ['Bob', 25],
            ['Charlie', 35],
            ['David', 40],
            ['Eve', 28],
        ];

        $table = Table::fromArray($data)
            ->skip(1)  // Skip Alice
            ->head(3); // Take Bob, Charlie, David

        $result = $table->toArray();
        $this->assertCount(4, $result); // header + 3 rows
        $this->assertEquals(['Bob', 25], $result[1]);
        $this->assertEquals(['Charlie', 35], $result[2]);
        $this->assertEquals(['David', 40], $result[3]);
    }
}
