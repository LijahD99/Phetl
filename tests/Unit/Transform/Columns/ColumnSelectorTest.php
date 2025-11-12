<?php

declare(strict_types=1);

namespace Phetl\Tests\Unit\Transform\Columns;

use InvalidArgumentException;
use Phetl\Table;
use PHPUnit\Framework\TestCase;

class ColumnSelectorTest extends TestCase
{
    public function test_it_selects_specific_columns(): void
    {
        $data = [
            ['name', 'age', 'city'],
            ['Alice', 30, 'NYC'],
            ['Bob', 25, 'LA'],
        ];

        $table = Table::fromArray($data)->selectColumns('name', 'city');

        $result = $table->toArray();
        $this->assertCount(3, $result);
        $this->assertEquals(['name', 'city'], $result[0]);
        $this->assertEquals(['Alice', 'NYC'], $result[1]);
        $this->assertEquals(['Bob', 'LA'], $result[2]);
    }

    public function test_it_selects_single_column(): void
    {
        $data = [
            ['name', 'age', 'city'],
            ['Alice', 30, 'NYC'],
            ['Bob', 25, 'LA'],
        ];

        $table = Table::fromArray($data)->selectColumns('age');

        $result = $table->toArray();
        $this->assertCount(3, $result);
        $this->assertEquals(['age'], $result[0]);
        $this->assertEquals([30], $result[1]);
        $this->assertEquals([25], $result[2]);
    }

    public function test_it_preserves_column_order(): void
    {
        $data = [
            ['name', 'age', 'city'],
            ['Alice', 30, 'NYC'],
        ];

        $table = Table::fromArray($data)->selectColumns('city', 'name');

        $result = $table->toArray();
        $this->assertEquals(['city', 'name'], $result[0]);
        $this->assertEquals(['NYC', 'Alice'], $result[1]);
    }

    public function test_it_throws_when_column_not_found(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Column 'nonexistent' not found");

        $data = [
            ['name', 'age'],
            ['Alice', 30],
        ];

        Table::fromArray($data)->selectColumns('nonexistent')->toArray();
    }

    public function test_it_throws_when_no_columns_specified(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one column must be specified');

        $data = [
            ['name', 'age'],
            ['Alice', 30],
        ];

        Table::fromArray($data)->selectColumns()->toArray();
    }

    public function test_it_handles_missing_values_in_rows(): void
    {
        $data = [
            ['name', 'age', 'city'],
            ['Alice', 30],  // Missing city
            ['Bob', 25, 'LA'],
        ];

        $table = Table::fromArray($data)->selectColumns('name', 'city');

        $result = $table->toArray();
        $this->assertEquals(['name', 'city'], $result[0]);
        $this->assertEquals(['Alice', null], $result[1]);
        $this->assertEquals(['Bob', 'LA'], $result[2]);
    }

    public function test_cut_is_alias_for_select_columns(): void
    {
        $data = [
            ['name', 'age', 'city'],
            ['Alice', 30, 'NYC'],
        ];

        $table = Table::fromArray($data)->cut('name', 'age');

        $result = $table->toArray();
        $this->assertEquals(['name', 'age'], $result[0]);
        $this->assertEquals(['Alice', 30], $result[1]);
    }

    public function test_it_removes_specific_columns(): void
    {
        $data = [
            ['name', 'age', 'city'],
            ['Alice', 30, 'NYC'],
            ['Bob', 25, 'LA'],
        ];

        $table = Table::fromArray($data)->removeColumns('age');

        $result = $table->toArray();
        $this->assertCount(3, $result);
        $this->assertEquals(['name', 'city'], $result[0]);
        $this->assertEquals(['Alice', 'NYC'], $result[1]);
        $this->assertEquals(['Bob', 'LA'], $result[2]);
    }

    public function test_it_removes_multiple_columns(): void
    {
        $data = [
            ['name', 'age', 'city', 'country'],
            ['Alice', 30, 'NYC', 'USA'],
        ];

        $table = Table::fromArray($data)->removeColumns('age', 'country');

        $result = $table->toArray();
        $this->assertEquals(['name', 'city'], $result[0]);
        $this->assertEquals(['Alice', 'NYC'], $result[1]);
    }

    public function test_it_returns_all_columns_when_removing_none(): void
    {
        $data = [
            ['name', 'age'],
            ['Alice', 30],
        ];

        $table = Table::fromArray($data)->removeColumns();

        $result = $table->toArray();
        $this->assertCount(2, $result);
        $this->assertEquals(['name', 'age'], $result[0]);
    }

    public function test_it_removes_nonexistent_columns_gracefully(): void
    {
        $data = [
            ['name', 'age'],
            ['Alice', 30],
        ];

        $table = Table::fromArray($data)->removeColumns('nonexistent', 'age');

        $result = $table->toArray();
        $this->assertEquals(['name'], $result[0]);
        $this->assertEquals(['Alice'], $result[1]);
    }

    public function test_cutout_is_alias_for_remove_columns(): void
    {
        $data = [
            ['name', 'age', 'city'],
            ['Alice', 30, 'NYC'],
        ];

        $table = Table::fromArray($data)->cutout('age');

        $result = $table->toArray();
        $this->assertEquals(['name', 'city'], $result[0]);
        $this->assertEquals(['Alice', 'NYC'], $result[1]);
    }

    public function test_column_operations_can_be_chained(): void
    {
        $data = [
            ['id', 'name', 'age', 'city', 'temp'],
            [1, 'Alice', 30, 'NYC', 'x'],
            [2, 'Bob', 25, 'LA', 'y'],
        ];

        $table = Table::fromArray($data)
            ->removeColumns('temp', 'id')
            ->selectColumns('name', 'city');

        $result = $table->toArray();
        $this->assertEquals(['name', 'city'], $result[0]);
        $this->assertEquals(['Alice', 'NYC'], $result[1]);
        $this->assertEquals(['Bob', 'LA'], $result[2]);
    }

    public function test_column_and_row_operations_can_be_chained(): void
    {
        $data = [
            ['name', 'age', 'city'],
            ['Alice', 30, 'NYC'],
            ['Bob', 25, 'LA'],
            ['Charlie', 35, 'SF'],
            ['David', 40, 'Chicago'],
        ];

        $table = Table::fromArray($data)
            ->selectColumns('name', 'age')
            ->head(2);

        $result = $table->toArray();
        $this->assertCount(3, $result); // header + 2 rows
        $this->assertEquals(['name', 'age'], $result[0]);
        $this->assertEquals(['Alice', 30], $result[1]);
        $this->assertEquals(['Bob', 25], $result[2]);
    }

    public function test_it_preserves_data_types_when_selecting(): void
    {
        $data = [
            ['name', 'age', 'active'],
            ['Alice', 30, true],
            ['Bob', 25, false],
        ];

        $table = Table::fromArray($data)->selectColumns('age', 'active');

        $result = $table->toArray();
        $this->assertIsInt($result[1][0]);
        $this->assertIsBool($result[1][1]);
        $this->assertEquals(30, $result[1][0]);
        $this->assertTrue($result[1][1]);
    }
}
